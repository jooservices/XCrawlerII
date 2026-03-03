<?php

declare(strict_types=1);

namespace Modules\JAV\Support;

final class CodeNormalizer
{
    /**
     * Normalize a raw movie/code string: uppercase, strip spaces, preserve A-Z0-9-.
     * FC2-PPV variants (e.g. fc2-ppv-123, FC2PPV123) are normalized to FC2-PPV-*.
     */
    public static function normalize(?string $raw): ?string
    {
        if ($raw === null || trim($raw) === '') {
            return null;
        }

        $s = trim(preg_replace('/\s+/', '', $raw));
        if ($s === '') {
            return null;
        }

        $upper = strtoupper($s);

        // FC2-PPV: normalize fc2ppv123456 -> FC2-PPV-123456, fc2-ppv-123 -> FC2-PPV-123
        if (preg_match('/^FC2[-]?PPV[-]?(\d+)$/i', $upper, $m)) {
            return 'FC2-PPV-' . $m[1];
        }

        // Keep only A-Z, 0-9, hyphen
        $filtered = preg_replace('/[^A-Z0-9\-]/', '', $upper);
        if ($filtered === '') {
            return null;
        }

        // Insert hyphen between letter run and digit run when adjacent (e.g. START498 -> START-498, XVSR866 -> XVSR-866)
        $filtered = preg_replace('/([A-Z]+)(\d+)/', '$1-$2', $filtered);

        return $filtered;
    }
}
