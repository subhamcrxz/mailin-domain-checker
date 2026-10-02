<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class BlacklistCheckerService
{
    /**
     * Common DNS-based blacklists.
     */
    private array $blacklists = [
        'zen.spamhaus.org' => 'Spamhaus ZEN',
        'bl.spamcop.net' => 'SpamCop',
        'b.barracudacentral.org' => 'Barracuda',
        'dnsbl.sorbs.net' => 'SORBS',
    ];

    /**
     * Check an IP address against configured DNSBLs.
     */
    public function checkIp(string $ip): array
    {
        if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return [
                'ip' => $ip,
                'status' => 'unknown',
                'blacklists' => [],
                'error' => 'Only IPv4 blacklist checks are currently supported.',
            ];
        }

        $reversedIp = implode('.', array_reverse(explode('.', $ip)));

        $listed = [];

        foreach ($this->blacklists as $zone => $name) {
            $query = "{$reversedIp}.{$zone}";

            try {
                $records = @dns_get_record($query, DNS_A);

                if (!empty($records)) {
                    $listed[] = [
                        'name' => $name,
                        'zone' => $zone,
                        'response' => $records,
                    ];
                }
            } catch (\Throwable $e) {
                Log::warning('Blacklist lookup failed', [
                    'ip' => $ip,
                    'blacklist' => $zone,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return [
            'ip' => $ip,
            'status' => empty($listed) ? 'clean' : 'listed',
            'blacklists' => $listed,
            'error' => null,
        ];
    }

    /**
     * Check all IPv4 addresses associated with a domain.
     */
    public function checkDomain(string $domain): array
    {
        $domain = trim(strtolower($domain), '.');

        $aRecords = @dns_get_record($domain, DNS_A) ?: [];

        $ips = [];

        foreach ($aRecords as $record) {
            if (!empty($record['ip'])) {
                $ips[] = $record['ip'];
            }
        }

        $ips = array_values(array_unique($ips));

        $results = [];

        foreach ($ips as $ip) {
            $results[] = $this->checkIp($ip);
        }

        $listed = [];

        foreach ($results as $result) {
            if ($result['status'] === 'listed') {
                $listed[] = $result;
            }
        }

        return [
            'domain' => $domain,
            'status' => empty($listed) ? 'clean' : 'listed',
            'ips' => $ips,
            'blacklists' => $listed,
            'checks' => $results,
        ];
    }
}