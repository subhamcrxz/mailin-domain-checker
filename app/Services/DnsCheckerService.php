<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use App\Exceptions\DnsLookupException;

class DnsCheckerService
{
    public function check(
        string $domain,
        ?string $dkimSelector = null
    ): array {
        $domain = $this->normalizeDomain($domain);

        return [
            'domain' => $domain,

            'a' => $this->getRecords(
                $domain,
                DNS_A
            ),

            'aaaa' => $this->getRecords(
                $domain,
                DNS_AAAA
            ),

            'mx' => $this->getRecords(
                $domain,
                DNS_MX
            ),

            'txt' => $this->getRecords(
                $domain,
                DNS_TXT
            ),

            'cname' => $this->getRecords(
                $domain,
                DNS_CNAME
            ),

            'ns' => $this->getRecords(
                $domain,
                DNS_NS
            ),

            'dmarc' => $this->getRecords(
                '_dmarc.' . $domain,
                DNS_TXT
            ),

            'spf' => $this->getSpfRecords($domain),

            'dkim' => $this->getDkimRecords(
                $domain,
                $dkimSelector
            ),

            'ptr' => $this->getPtrRecords($domain),

            'all' => $this->getAllRecords($domain),
        ];
    }

    private function normalizeDomain(string $input): string
    {
        $input = trim(strtolower($input));

        if (filter_var($input, FILTER_VALIDATE_EMAIL)) {
            $domain = substr(
                strrchr($input, '@'),
                1
            );
        } else {
            $domain = preg_replace(
                '#^https?://#',
                '',
                $input
            );

            $domain = explode(
                '/',
                $domain
            )[0];

            $domain = explode(
                ':',
                $domain
            )[0];
        }

        return trim($domain, '.');
    }

    private function getRecords(
        string $domain,
        int $type
    ): array {
        try {
            $records = @dns_get_record(
                $domain,
                $type
            );

            if ($records === false) {
                Log::warning(
                    'DNS lookup returned no result',
                    [
                        'domain' => $domain,
                        'type' => $type,
                    ]
                );

                return [];
            }

            return $records;
        } catch (\Throwable $e) {
            Log::warning(
                'DNS lookup failed',
                [
                    'domain' => $domain,
                    'type' => $type,
                    'error' => $e->getMessage(),
                ]
            );

            throw new DnsLookupException(
                "DNS lookup failed for {$domain}.",
                0,
                $e
            );
        }
    }

    private function getSpfRecords(
        string $domain
    ): array {
        $records = $this->getRecords(
            $domain,
            DNS_TXT
        );

        return array_values(
            array_filter(
                $records,
                function (array $record): bool {
                    return isset($record['txt'])
                        && str_starts_with(
                            strtolower($record['txt']),
                            'v=spf1'
                        );
                }
            )
        );
    }

    /**
     * Check a DKIM TXT record when a selector
     * has been provided.
     *
     * Example:
     *
     * selector1._domainkey.example.com
     */
    private function getDkimRecords(
        string $domain,
        ?string $selector
    ): array {
        if ($selector === null) {
            return [];
        }

        $selector = trim($selector);

        if ($selector === '') {
            return [];
        }

        $dkimDomain =
            $selector . '._domainkey.' . $domain;

        return $this->getRecords(
            $dkimDomain,
            DNS_TXT
        );
    }

    private function getPtrRecords(
        string $domain
    ): array {
        $aRecords = $this->getRecords(
            $domain,
            DNS_A
        );

        $results = [];

        foreach ($aRecords as $record) {
            if (!isset($record['ip'])) {
                continue;
            }

            $ip = $record['ip'];

            $ptr = @gethostbyaddr($ip);

            $results[] = [
                'ip' => $ip,
                'ptr' => $ptr !== $ip
                    ? $ptr
                    : null,
            ];
        }

        return $results;
    }

    private function getAllRecords(
        string $domain
    ): array {
        $records = @dns_get_record(
            $domain,
            DNS_ALL
        );

        if ($records === false) {
            return [];
        }

        return $records;
    }
}