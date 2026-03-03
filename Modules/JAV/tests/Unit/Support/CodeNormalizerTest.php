<?php

declare(strict_types=1);

namespace Modules\JAV\Tests\Unit\Support;

use Modules\JAV\Support\CodeNormalizer;
use Modules\JAV\Tests\TestCase;

final class CodeNormalizerTest extends TestCase
{
    public function test_normalize_returns_uppercase(): void
    {
        $this->assertSame('ABC-123', CodeNormalizer::normalize('abc-123'));
        $this->assertSame('SSIS-001', CodeNormalizer::normalize('ssis-001'));
    }

    public function test_normalize_strips_spaces(): void
    {
        $this->assertSame('ABC-123', CodeNormalizer::normalize('  ABC 123  '));
        $this->assertSame('FC2-PPV-123', CodeNormalizer::normalize('fc2 ppv 123'));
    }

    public function test_normalize_fc2_ppv_handling(): void
    {
        $this->assertSame('FC2-PPV-4857395', CodeNormalizer::normalize('fc2ppv4857395'));
        $this->assertSame('FC2-PPV-4857395', CodeNormalizer::normalize('FC2-PPV-4857395'));
        $this->assertSame('FC2-PPV-123', CodeNormalizer::normalize('fc2-ppv-123'));
    }

    public function test_normalize_returns_null_for_empty_input(): void
    {
        $this->assertNull(CodeNormalizer::normalize(null));
        $this->assertNull(CodeNormalizer::normalize(''));
        $this->assertNull(CodeNormalizer::normalize('   '));
    }

    public function test_normalize_preserves_az_digits_hyphen(): void
    {
        $this->assertSame('ABC-123-X', CodeNormalizer::normalize('abc-123-x'));
        $this->assertSame('MIDE-654', CodeNormalizer::normalize('MIDE-654'));
    }

    public function test_normalize_inserts_hyphen_between_letters_and_digits(): void
    {
        $this->assertSame('START-498', CodeNormalizer::normalize('START498'));
        $this->assertSame('XVSR-866', CodeNormalizer::normalize('XVSR866'));
        $this->assertSame('SSIS-001', CodeNormalizer::normalize('SSIS001'));
        $this->assertSame('START-498', CodeNormalizer::normalize('start498'));
        $this->assertSame('FNS-175', CodeNormalizer::normalize('FNS175'));
        $this->assertSame('VDD-202', CodeNormalizer::normalize('VDD202'));
        $this->assertSame('WAAA-619', CodeNormalizer::normalize('WAAA619'));
        $this->assertSame('YMDS-261', CodeNormalizer::normalize('YMDS261'));
    }

    public function test_normalize_does_not_double_hyphen_when_already_present(): void
    {
        $this->assertSame('START-498', CodeNormalizer::normalize('START-498'));
        $this->assertSame('MIDE-654', CodeNormalizer::normalize('MIDE-654'));
    }
}
