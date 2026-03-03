<?php

declare(strict_types=1);

namespace Modules\JAV\Tests\Unit\Exceptions;

use Modules\JAV\Exceptions\UnsupportedSourceException;
use Modules\JAV\Tests\TestCase;

final class UnsupportedSourceExceptionTest extends TestCase
{
    public function test_for_source_returns_exception_with_message(): void
    {
        $e = UnsupportedSourceException::forSource('xcity');

        $this->assertInstanceOf(UnsupportedSourceException::class, $e);
        $this->assertSame('Unsupported source: xcity', $e->getMessage());
    }

    public function test_for_source_with_arbitrary_string(): void
    {
        $e = UnsupportedSourceException::forSource('unknown-source');

        $this->assertSame('Unsupported source: unknown-source', $e->getMessage());
    }

    public function test_extends_runtime_exception(): void
    {
        $e = UnsupportedSourceException::forSource('test');

        $this->assertInstanceOf(\RuntimeException::class, $e);
    }
}
