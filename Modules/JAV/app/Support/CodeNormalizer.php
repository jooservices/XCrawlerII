<?php

namespace Modules\JAV\Support;

class CodeNormalizer
{
    public static function normalize(?string $raw): ?string
    {
        if ($raw === null) {
            return null;
        }

        $raw = trim($raw);
        if ($raw === '') {
            return null;
        }

        $normalized = strtoupper($raw);
        $normalized = preg_replace('/\s+/', '', $normalized) ?? $normalized;
        $normalized = preg_replace('/[^A-Z0-9-]/', '', $normalized) ?? $normalized;

        if (str_starts_with($normalized, 'FC2PPV')) {
            $normalized = 'FC2-PPV'.substr($normalized, 6);
        }

        if (str_starts_with($normalized, 'FC2-PPV')) {
            $suffix = preg_replace('/[^0-9A-Z]/', '', substr($normalized, 7)) ?? '';

            return $suffix !== '' ? 'FC2-PPV'.$suffix : 'FC2-PPV';
        }

        if (preg_match('/^([A-Z]+)-?([0-9][0-9A-Z]*)$/', $normalized, $matches)) {
            return $matches[1].'-'.$matches[2];
        }

        return $normalized;
    }

    public static function compactIdFromCode(?string $code): ?string
    {
        $code = self::normalize($code);
        if ($code === null) {
            return null;
        }

        return strtolower(str_replace('-', '', $code));
    }
}
