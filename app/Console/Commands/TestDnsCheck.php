<?php

namespace App\Console\Commands;

use App\Services\DnsCheckerService;
use Illuminate\Console\Command;

class TestDnsCheck extends Command
{
    protected $signature = 'dns:check {domain}';

    protected $description = 'Test DNS records for a domain';

    public function handle(DnsCheckerService $dnsChecker): int
    {
        $domain = $this->argument('domain');

        $result = $dnsChecker->check($domain);

        $this->line(
            json_encode(
                $result,
                JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
            )
        );

        return self::SUCCESS;
    }
}