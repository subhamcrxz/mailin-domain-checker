<?php

namespace App\Jobs;

use App\Models\DomainCheck;
use App\Services\DomainCheckService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\Middleware\RateLimited;
use Throwable;

class ProcessDomainCheck implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 30;

    public function backoff(): array
    {
        return [2, 5];
    }

    public function __construct(
        public int $domainCheckId
    ) {
        $this->onQueue('domain-checks');
    }

    public function middleware(): array
    {
        return [
            new RateLimited('domain-checks'),
        ];
    }

    public function handle(DomainCheckService $service): void
    {
        $check = DomainCheck::find($this->domainCheckId);

        if (!$check) {
            return;
        }

        /*
         * queued -> checking
         */
        if ($check->batch_id) {
            DB::table('check_batches')
                ->where('id', $check->batch_id)
                ->update([
                    'queued' => DB::raw(
                        'CASE WHEN queued > 0 THEN queued - 1 ELSE 0 END'
                    ),
                    'checking' => DB::raw('checking + 1'),
                    'status' => 'processing',
                    'updated_at' => now(),
                ]);
        }

        $check->update([
            'status' => 'checking',
            'started_at' => now(),
            'error' => null,
        ]);

        try {
            $result = $service->check(
                $check->input,
                $check->dkim_selector
            );

            $check->update([
                'status' => 'completed',

                'blacklist_status' =>
                    $result['blacklist']['status'] ?? 'unknown',

                'blacklists' =>
                    $result['blacklist']['blacklists'] ?? [],

                'dns_records' =>
                    $result['dns'] ?? [],

                'provider' =>
                    $result['provider']['provider'] ?? null,

                'detection_evidence' =>
                    $result['provider']['evidence'] ?? null,

                'error' => null,

                'completed_at' => now(),
            ]);

            /*
             * checking -> completed
             */
            if ($check->batch_id) {
                DB::table('check_batches')
                    ->where('id', $check->batch_id)
                    ->update([
                        'checking' => DB::raw(
                            'CASE WHEN checking > 0 THEN checking - 1 ELSE 0 END'
                        ),
                        'completed' => DB::raw('completed + 1'),
                        'updated_at' => now(),
                    ]);
            }
        } catch (Throwable $e) {
            Log::warning('Domain check attempt failed', [
                'domain_check_id' => $check->id,
                'input' => $check->input,
                'attempt' => $this->attempts(),
                'max_attempts' => $this->tries,
                'error' => $e->getMessage(),
            ]);

            /*
             * Laravel will retry the job.
             * Do not count it as permanently failed yet.
             */
            if ($this->attempts() < $this->tries) {
                $check->update([
                    'status' => 'queued',
                ]);

                if ($check->batch_id) {
                    DB::table('check_batches')
                        ->where('id', $check->batch_id)
                        ->update([
                            'queued' => DB::raw('queued + 1'),
                            'checking' => DB::raw(
                                'CASE WHEN checking > 0 THEN checking - 1 ELSE 0 END'
                            ),
                            'updated_at' => now(),
                        ]);
                }
            }

            throw $e;
        }
    }

    /**
     * Called only after all retry attempts are exhausted.
     */
    public function failed(?Throwable $exception): void
    {
        $check = DomainCheck::find($this->domainCheckId);

        if (!$check) {
            return;
        }

        $error = $exception?->getMessage() ?? 'Domain check failed.';

        Log::error('Domain check permanently failed', [
            'domain_check_id' => $check->id,
            'input' => $check->input,
            'attempts' => $this->tries,
            'error' => $error,
        ]);

        $check->update([
            'status' => 'failed',
            'error' => $error,
            'completed_at' => now(),
        ]);

        /*
         * checking -> failed
         */
        if ($check->batch_id) {
            DB::table('check_batches')
                ->where('id', $check->batch_id)
                ->update([
                    'checking' => DB::raw(
                        'CASE WHEN checking > 0 THEN checking - 1 ELSE 0 END'
                    ),
                    'failed' => DB::raw('failed + 1'),
                    'updated_at' => now(),
                ]);
        }
    }
}