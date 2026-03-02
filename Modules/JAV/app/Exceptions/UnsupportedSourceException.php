<?php

declare(strict_types=1);

namespace Modules\JAV\Exceptions;

use RuntimeException;

final class UnsupportedSourceException extends RuntimeException
{
    public static function forSource(string $source): self
    {
        return new self("Unsupported source: {$source}");
    }
}
