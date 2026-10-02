<?php

namespace App\Console\Commands;

use App\Services\DomainCheckService;
use Illuminate\Console\Command;

class TestDomainCheck extends Command
{
    protected $signature = 'domain:check {input}';

    protected $description = 'Run complete domain check';

    public function handle(DomainCheckService $service): int
    {
        $result = $service->check($this->argument('input'));

        $this->line(
            json_encode(
                $result,
                JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
            )
        );

        return self::SUCCESS;
    }
}