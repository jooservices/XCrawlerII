<?php

namespace Modules\JAV\Tests\Unit\Support;

use Modules\JAV\Support\CodeNormalizer;
use Tests\TestCase;

class CodeNormalizerTest extends TestCase
{
    public function test_normalize_handles_null_empty_and_whitespace_values(): void
    {
        $this->assertNull(CodeNormalizer::normalize(null));
        $this->assertNull(CodeNormalizer::normalize(''));
        $this->assertNull(CodeNormalizer::normalize('   '));
    }

    public function test_normalize_formats_standard_codes_with_hyphen(): void
    {
        $this->assertSame('ABP-123', CodeNormalizer::normalize('abp123'));
        $this->assertSame('MIDE-777', CodeNormalizer::normalize(' MIDE  777 '));
        $this->assertSame('SSIS-001A', CodeNormalizer::normalize('ssis-001a'));
    }

    public function test_normalize_fc2_ppv_variants(): void
    {
        $this->assertSame('FC2-PPV4846656', CodeNormalizer::normalize('fc2ppv-4846656'));
        $this->assertSame('FC2-PPV4846667', CodeNormalizer::normalize('FC2 PPV 4846667'));
        $this->assertSame('FC2-PPV', CodeNormalizer::normalize('fc2-ppv'));
    }

    public function test_normalize_removes_symbols_but_preserves_unknown_shape_when_no_pattern_match(): void
    {
        $this->assertSame('ABCD-123X', CodeNormalizer::normalize('a@b#c$d-123x'));
    }

    public function test_compact_id_from_code_returns_lowercase_without_hyphen(): void
    {
        $this->assertSame('abp123', CodeNormalizer::compactIdFromCode('ABP-123'));
        $this->assertSame('fc2ppv4846656', CodeNormalizer::compactIdFromCode('fc2ppv4846656'));
        $this->assertNull(CodeNormalizer::compactIdFromCode('   '));
    }
}
