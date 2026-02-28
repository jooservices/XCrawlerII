<?php

declare(strict_types=1);

namespace Modules\Core\Constants\Events;

/**
 * Keys that must be stripped or redacted from event payloads before persistence.
 */
final class PayloadSanitizerKeys
{
    /** @var list<string> */
    public const SANITIZED_KEYS = [
        'password',
        'password_confirmation',
        'token',
        'access_token',
        'refresh_token',
        'api_key',
        'secret',
        'authorization',
        'cookie',
        'credit_card',
        'ssn',
    ];

    public const REDACT_PLACEHOLDER = '[REDACTED]';

    public static function shouldSanitize(string $key): bool
    {
        $lower = strtolower($key);

        foreach (self::SANITIZED_KEYS as $s) {
            if ($lower === $s || str_contains($lower, $s)) {
                return true;
            }
        }

        return false;
    }
}
