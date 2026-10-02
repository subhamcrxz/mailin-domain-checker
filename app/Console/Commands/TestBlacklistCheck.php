<?php

namespace App\Console\Commands;

use App\Services\BlacklistCheckerService;
use Illuminate\Console\Command;

class TestBlacklistCheck extends Command
{
    protected $signature = 'blacklist:check {domain}';

    protected $description = 'Check a domain against DNS blacklists';

    public function handle(BlacklistCheckerService $checker): int
    {
        $domain = $this->argument('domain');

        $result = $checker->checkDomain($domain);

        $this->line(
            json_encode(
                $result,
                JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
            )
        );

        return self::SUCCESS;
    }
}