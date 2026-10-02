<?php

namespace App\Services;

class MailProviderDetector
{
    public function detect(array $mxRecords, array $txtRecords = []): array
    {
        $targets = collect($mxRecords)
            ->pluck('target')
            ->filter()
            ->map(fn ($target) => strtolower(rtrim($target, '.')))
            ->values()
            ->all();

        $googlePatterns = [
            'google.com',
            'googlemail.com',
            'gmail.com',
            'googleusercontent.com',
            'l.google.com',
        ];

        $microsoftPatterns = [
            'outlook.com',
            'outlook.office365.com',
            'protection.outlook.com',
            'mail.protection.outlook.com',
            'microsoft.com',
        ];

        foreach ($targets as $target) {
            foreach ($googlePatterns as $pattern) {
                if (
                    $target === $pattern ||
                    str_ends_with($target, '.' . $pattern)
                ) {
                    return [
                        'provider' => 'Google Workspace',
                        'status' => 'Detected',
                        'evidence' => "MX record {$target} matches Google mail infrastructure.",
                    ];
                }
            }
        }

        foreach ($targets as $target) {
            foreach ($microsoftPatterns as $pattern) {
                if (
                    $target === $pattern ||
                    str_ends_with($target, '.' . $pattern)
                ) {
                    return [
                        'provider' => 'Microsoft 365',
                        'status' => 'Detected',
                        'evidence' => "MX record {$target} matches Microsoft 365 mail infrastructure.",
                    ];
                }
            }
        }

        if (!empty($targets)) {
            return [
                'provider' => 'Other',
                'status' => 'Detected',
                'evidence' => 'MX records were found, but they do not match known Google Workspace or Microsoft 365 infrastructure.',
            ];
        }

        return [
            'provider' => 'Not Detected',
            'status' => 'Not Detected',
            'evidence' => 'No MX records were detected.',
        ];
    }
}