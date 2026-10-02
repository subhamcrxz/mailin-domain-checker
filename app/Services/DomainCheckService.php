<?php

namespace App\Services;

class DomainCheckService
{
    public function __construct(
        private DnsCheckerService $dnsChecker,
        private BlacklistCheckerService $blacklistChecker,
        private MailProviderDetector $providerDetector,
    ) {
    }

    public function check(
        string $input,
        ?string $dkimSelector = null
    ): array {
        $domain = $this->normalizeDomain($input);

        $dns = $this->dnsChecker->check(
            $domain,
            $dkimSelector
        );

        $blacklist = $this->blacklistChecker
            ->checkDomain($domain);

        $provider = $this->providerDetector->detect(
            $dns['mx'] ?? [],
            $dns['txt'] ?? []
        );

        return [
            'input' => $input,

            'domain' => $domain,

            'dns' => $dns,

            'blacklist' => $blacklist,

            'provider' => $provider,
        ];
    }

    private function normalizeDomain(
        string $input
    ): string {
        $input = trim(
            strtolower($input)
        );

        if (filter_var(
            $input,
            FILTER_VALIDATE_EMAIL
        )) {
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

        return trim(
            $domain,
            '.'
        );
    }
}