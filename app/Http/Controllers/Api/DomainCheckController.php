<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessDomainCheck;
use App\Models\CheckBatch;
use App\Models\DomainCheck;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DomainCheckController extends Controller
{
    /**
     * Queue a single domain/email check.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make(
            $request->all(),
            [
                'input' => [
                    'required',
                    'string',
                    'max:255',
                ],
                'dkim_selector' => [
                    'nullable',
                    'string',
                    'max:100',
                ],
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Invalid input.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $input = trim($request->input('input'));

        $domain = $this->normalizeDomain($input);

        if (!$domain) {
            return response()->json([
                'message' => 'Unable to determine a valid domain.',
            ], 422);
        }

        $dkimSelector = $request->input('dkim_selector');

        if (is_string($dkimSelector)) {
            $dkimSelector = trim($dkimSelector);

            if ($dkimSelector === '') {
                $dkimSelector = null;
            }
        }

        $check = DomainCheck::create([
            'input' => $input,
            'domain' => $domain,
            'dkim_selector' => $dkimSelector,
            'status' => 'queued',
        ]);

        ProcessDomainCheck::dispatch($check->id);

        return response()->json([
            'message' => 'Domain check queued.',
            'id' => $check->id,
            'status' => $check->status,
            'domain' => $check->domain,
        ], 202);
    }

    /**
     * Show a single domain check.
     */
    public function show(DomainCheck $domainCheck): JsonResponse
    {
        return response()->json([
            'id' => $domainCheck->id,
            'batch_id' => $domainCheck->batch_id,
            'input' => $domainCheck->input,
            'domain' => $domainCheck->domain,
            'dkim_selector' => $domainCheck->dkim_selector,
            'status' => $domainCheck->status,
            'blacklist_status' => $domainCheck->blacklist_status,
            'blacklists' => $domainCheck->blacklists ?? [],
            'dns_records' => $domainCheck->dns_records ?? [],
            'provider' => $domainCheck->provider,
            'detection_evidence' => $domainCheck->detection_evidence,
            'error' => $domainCheck->error,
            'started_at' => $domainCheck->started_at,
            'completed_at' => $domainCheck->completed_at,
            'created_at' => $domainCheck->created_at,
            'updated_at' => $domainCheck->updated_at,
        ]);
    }

    /**
     * Queue a bulk CSV/TXT domain check.
     */
    public function bulkStore(Request $request): JsonResponse
    {
        $validator = Validator::make(
            $request->all(),
            [
                'file' => [
                    'required',
                    'file',
                    'max:10240',
                ],
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Invalid upload.',
                'errors' => $validator->errors(),
            ], 422);
        }

        /** @var UploadedFile $file */
        $file = $request->file('file');

        $extension = strtolower(
            $file->getClientOriginalExtension()
        );

        if (!in_array($extension, ['csv', 'txt'], true)) {
            return response()->json([
                'message' => 'Only CSV and TXT files are supported.',
            ], 422);
        }

        $rows = $this->parseBulkFile(
            $file,
            $extension
        );

        if (empty($rows)) {
            return response()->json([
                'message' =>
                    'No valid domains or email addresses were found in the file.',
            ], 422);
        }

        /*
         * Prevent an accidentally huge upload from creating
         * an unreasonable number of queue jobs.
         */
        $maxRows = (int) config(
            'domain-check.max_bulk_rows',
            10000
        );

        if (count($rows) > $maxRows) {
            return response()->json([
                'message' =>
                    "The maximum allowed rows per upload is {$maxRows}.",
                'max_rows' => $maxRows,
            ], 422);
        }

        $batch = null;

        $checkIds = [];

        DB::transaction(function () use (
            &$batch,
            &$checkIds,
            $rows,
            $file
        ): void {
            $batch = CheckBatch::create([
                'type' => 'bulk',
                'filename' => $file->getClientOriginalName(),
                'total' => count($rows),
                'queued' => count($rows),
                'checking' => 0,
                'completed' => 0,
                'failed' => 0,
                'status' => 'queued',
            ]);

            foreach ($rows as $row) {
                $check = DomainCheck::create([
                    'batch_id' => $batch->id,
                    'input' => $row['input'],
                    'domain' => $row['domain'],
                    'dkim_selector' => $row['dkim_selector'] ?? null,
                    'status' => 'queued',
                ]);

                $checkIds[] = $check->id;
            }
        });

        /*
         * Dispatch only after the DB transaction has committed.
         * This prevents workers from seeing incomplete records.
         */
        foreach ($checkIds as $checkId) {
            ProcessDomainCheck::dispatch($checkId)
                ->afterCommit();
        }

        return response()->json([
            'message' => 'Bulk domain check queued.',
            'batch_id' => $batch->id,
            'total' => $batch->total,
            'queued' => $batch->queued,
            'checking' => $batch->checking,
            'completed' => $batch->completed,
            'failed' => $batch->failed,
            'status' => $batch->status,
        ], 202);
    }

    /**
     * Show live progress for a bulk batch.
     */
    public function batchShow(CheckBatch $batch): JsonResponse
    {
        $batch->refresh();

        $processed =
            $batch->completed +
            $batch->failed;

        $progress = $batch->total > 0
            ? round(
                ($processed / $batch->total) * 100,
                2
            )
            : 0;

        /*
         * Mark the batch as completed once all jobs
         * have either completed or permanently failed.
         */
        if (
            $batch->total > 0 &&
            $processed >= $batch->total
        ) {
            $status =
                $batch->failed > 0
                    ? 'completed_with_errors'
                    : 'completed';

            if ($batch->status !== $status) {
                $batch->update([
                    'status' => $status,
                ]);

                $batch->refresh();
            }
        }

        return response()->json([
            'id' => $batch->id,
            'type' => $batch->type,
            'filename' => $batch->filename,
            'status' => $batch->status,
            'total' => $batch->total,
            'queued' => $batch->queued,
            'checking' => $batch->checking,
            'completed' => $batch->completed,
            'failed' => $batch->failed,
            'processed' => $processed,
            'progress' => $progress,
        ]);
    }

    /**
     * Return the individual results belonging to a batch.
     *
     * Optional filters:
     *
     * ?status=completed
     * ?status=failed
     * ?blacklist=clean
     * ?blacklist=listed
     * ?provider=Google%20Workspace
     * ?provider=Microsoft%20365
     * ?provider=Other
     * ?provider=Not%20Detected
     */
    public function batchResults(
        Request $request,
        CheckBatch $batch
    ): JsonResponse {
        $query = DomainCheck::query()
            ->where('batch_id', $batch->id);

        /*
         * Status filter.
         */
        $status = trim(
            (string) $request->input('status', '')
        );

        if (
            $status !== '' &&
            in_array(
                $status,
                [
                    'queued',
                    'checking',
                    'completed',
                    'failed',
                ],
                true
            )
        ) {
            $query->where('status', $status);
        }

        /*
         * Blacklist filter.
         */
        $blacklist = strtolower(
            trim(
                (string) $request->input('blacklist', '')
            )
        );

        if ($blacklist === 'clean') {
            $query->where(
                'blacklist_status',
                'clean'
            );
        } elseif (
            in_array(
                $blacklist,
                ['listed', 'blacklisted'],
                true
            )
        ) {
            $query->where(
                'blacklist_status',
                'listed'
            );
        }

        /*
         * Provider filter.
         */
        $provider = trim(
            (string) $request->input('provider', '')
        );

        if ($provider !== '') {
            if (
                strtolower($provider) === 'not detected'
            ) {
                $query->where(function ($q): void {
                    $q->whereNull('provider')
                        ->orWhere(
                            'provider',
                            'Not Detected'
                        );
                });
            } else {
                $query->where(
                    'provider',
                    $provider
                );
            }
        }

        $results = $query
            ->orderBy('id')
            ->get()
            ->map(function (DomainCheck $check): array {
                return [
                    'id' => $check->id,
                    'batch_id' => $check->batch_id,
                    'input' => $check->input,
                    'domain' => $check->domain,
                    'dkim_selector' =>
                        $check->dkim_selector,
                    'status' => $check->status,
                    'blacklist_status' =>
                        $check->blacklist_status,
                    'blacklists' =>
                        $check->blacklists ?? [],
                    'dns_records' =>
                        $check->dns_records ?? [],
                    'provider' => $check->provider,
                    'detection_evidence' =>
                        $check->detection_evidence,
                    'error' => $check->error,
                    'started_at' =>
                        $check->started_at,
                    'completed_at' =>
                        $check->completed_at,
                    'created_at' =>
                        $check->created_at,
                    'updated_at' =>
                        $check->updated_at,
                ];
            })
            ->values();

        return response()->json([
            'batch_id' => $batch->id,
            'total' => $batch->total,
            'count' => $results->count(),
            'results' => $results,
        ]);
    }

    /**
     * Export completed/failed batch results as CSV.
     */
    public function exportBatch(
        CheckBatch $batch
    ): StreamedResponse {
        $filename = sprintf(
            'domain-check-results-%d.csv',
            $batch->id
        );

        return response()->streamDownload(
            function () use ($batch): void {
                $handle = fopen('php://output', 'wb');

                if ($handle === false) {
                    return;
                }

                /*
                 * CSV header.
                 */
                fputcsv($handle, [
                    'ID',
                    'Input',
                    'Domain',
                    'Status',
                    'Blacklist Status',
                    'Blacklists',
                    'Provider',
                    'Detection Evidence',
                    'MX',
                    'SPF',
                    'DMARC',
                    'DKIM',
                    'A',
                    'AAAA',
                    'CNAME',
                    'NS',
                    'PTR',
                    'Error',
                    'Started At',
                    'Completed At',
                ]);

                /*
                 * Process rows in chunks so large batches
                 * do not consume excessive memory.
                 */
                DomainCheck::query()
                    ->where('batch_id', $batch->id)
                    ->whereIn(
                        'status',
                        [
                            'completed',
                            'failed',
                        ]
                    )
                    ->orderBy('id')
                    ->chunkById(
                        500,
                        function ($checks) use (
                            $handle
                        ): void {
                            foreach ($checks as $check) {
                                $dns =
                                    is_array(
                                        $check->dns_records
                                    )
                                        ? $check->dns_records
                                        : [];

                                fputcsv($handle, [
                                    $check->id,
                                    $check->input,
                                    $check->domain,
                                    $check->status,
                                    $check->blacklist_status,
                                    $this->csvValue(
                                        $check->blacklists
                                    ),
                                    $check->provider,
                                    $check->detection_evidence,
                                    $this->csvDnsValue(
                                        $dns['mx'] ?? []
                                    ),
                                    $this->csvDnsValue(
                                        $dns['spf'] ?? []
                                    ),
                                    $this->csvDnsValue(
                                        $dns['dmarc'] ?? []
                                    ),
                                    $this->csvDnsValue(
                                        $dns['dkim'] ?? []
                                    ),
                                    $this->csvDnsValue(
                                        $dns['a'] ?? []
                                    ),
                                    $this->csvDnsValue(
                                        $dns['aaaa'] ?? []
                                    ),
                                    $this->csvDnsValue(
                                        $dns['cname'] ?? []
                                    ),
                                    $this->csvDnsValue(
                                        $dns['ns'] ?? []
                                    ),
                                    $this->csvDnsValue(
                                        $dns['ptr'] ?? []
                                    ),
                                    $check->error,
                                    $check->started_at,
                                    $check->completed_at,
                                ]);
                            }
                        }
                    );

                fclose($handle);
            },
            $filename,
            [
                'Content-Type' =>
                    'text/csv; charset=UTF-8',
                'Content-Disposition' =>
                    'attachment; filename="' .
                    $filename .
                    '"',
                'Cache-Control' =>
                    'no-store, no-cache',
            ]
        );
    }

    /**
     * Parse a CSV/TXT bulk upload.
     */
    private function parseBulkFile(
        UploadedFile $file,
        string $extension
    ): array {
        $results = [];

        $handle = fopen(
            $file->getRealPath(),
            'rb'
        );

        if ($handle === false) {
            return [];
        }

        $isFirstRow = true;

        while (
            (
                $row =
                    $extension === 'csv'
                        ? fgetcsv($handle)
                        : fgets($handle)
            ) !== false
        ) {
            if ($extension === 'csv') {
                $input = trim(
                    (string) ($row[0] ?? '')
                );

                /*
                 * Optional DKIM selector column.
                 *
                 * Example:
                 *
                 * domain,dkim_selector
                 * gmail.com,20230601
                 */
                $dkimSelector = null;

                if (
                    isset($row[1]) &&
                    trim((string) $row[1]) !== ''
                ) {
                    $dkimSelector = trim(
                        (string) $row[1]
                    );
                }
            } else {
                $input = trim(
                    (string) $row
                );

                $dkimSelector = null;
            }

            /*
             * Remove UTF-8 BOM from the first field.
             */
            if ($isFirstRow) {
                $input = preg_replace(
                    '/^\xEF\xBB\xBF/',
                    '',
                    $input
                ) ?? $input;
            }

            $isFirstRow = false;

            if ($input === '') {
                continue;
            }

            /*
             * Ignore common CSV headers.
             */
            if (
                in_array(
                    strtolower($input),
                    [
                        'domain',
                        'domains',
                        'email',
                        'emails',
                        'input',
                    ],
                    true
                )
            ) {
                continue;
            }

            $domain = $this->normalizeDomain(
                $input
            );

            if (!$domain) {
                continue;
            }

            $results[] = [
                'input' => $input,
                'domain' => $domain,
                'dkim_selector' => $dkimSelector,
            ];
        }

        fclose($handle);

        return $results;
    }

    /**
     * Normalize a domain or email address into a domain.
     */
    private function normalizeDomain(
        string $input
    ): ?string {
        $input = trim(
            strtolower($input)
        );

        if ($input === '') {
            return null;
        }

        /*
         * Email address.
         */
        if (
            filter_var(
                $input,
                FILTER_VALIDATE_EMAIL
            )
        ) {
            $domain = substr(
                strrchr($input, '@'),
                1
            );
        } else {
            /*
             * Remove HTTP/HTTPS.
             */
            $domain = preg_replace(
                '#^https?://#',
                '',
                $input
            );

            /*
             * Remove path.
             */
            $domain = explode(
                '/',
                $domain
            )[0];

            /*
             * Remove port.
             */
            $domain = explode(
                ':',
                $domain
            )[0];

            /*
             * Remove whitespace.
             */
            $domain = trim($domain);
        }

        $domain = trim(
            $domain,
            '.'
        );

        if ($domain === '') {
            return null;
        }

        /*
         * Basic hostname validation.
         *
         * Allows normal domains and subdomains.
         */
        if (
            !preg_match(
                '/^(?=.{1,253}$)(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,63}$/i',
                $domain
            )
        ) {
            return null;
        }

        return $domain;
    }

    /**
     * Convert an array/value into a CSV-safe string.
     */
    private function csvValue(
        mixed $value
    ): string {
        if ($value === null) {
            return '';
        }

        if (is_scalar($value)) {
            return (string) $value;
        }

        return json_encode(
            $value,
            JSON_UNESCAPED_SLASHES |
            JSON_UNESCAPED_UNICODE
        ) ?: '';
    }

    /**
     * Convert DNS result arrays into readable CSV values.
     */
    private function csvDnsValue(
        mixed $records
    ): string {
        if (!is_array($records)) {
            return '';
        }

        $values = [];

        foreach ($records as $record) {
            if (!is_array($record)) {
                $values[] = (string) $record;
                continue;
            }

            /*
             * Common DNS fields.
             */
            foreach (
                [
                    'target',
                    'ip',
                    'ipv6',
                    'txt',
                    'mname',
                    'rname',
                    'host',
                    'ns',
                    'cname',
                    'ptr',
                    'pri',
                    'port',
                ] as $field
            ) {
                if (
                    isset($record[$field]) &&
                    $record[$field] !== ''
                ) {
                    $values[] =
                        $field . '=' .
                        $record[$field];

                    break;
                }
            }

            /*
             * If no known field was found,
             * preserve the complete record.
             */
            if (
                empty($values) ||
                !is_string(
                    end($values)
                )
            ) {
                $values[] = json_encode(
                    $record,
                    JSON_UNESCAPED_SLASHES |
                    JSON_UNESCAPED_UNICODE
                ) ?: '';
            }
        }

        return implode(
            ' | ',
            $values
        );
    }
}