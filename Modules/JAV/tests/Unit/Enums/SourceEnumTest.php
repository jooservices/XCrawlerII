<?php

declare(strict_types=1);

namespace Modules\JAV\Tests\Unit\Enums;

use Modules\JAV\Enums\SourceEnum;
use Modules\JAV\Tests\TestCase;

final class SourceEnumTest extends TestCase
{
    public function test_onejav_value(): void
    {
        $this->assertSame('onejav', SourceEnum::Onejav->value);
    }

    public function test_one_four_one_jav_value(): void
    {
        $this->assertSame('141jav', SourceEnum::OneFourOneJav->value);
    }

    public function test_ff_jav_value(): void
    {
        $this->assertSame('ffjav', SourceEnum::FfJav->value);
    }

    public function test_xcity_value(): void
    {
        $this->assertSame('xcity', SourceEnum::Xcity->value);
    }

    public function test_all_cases_are_string_backed(): void
    {
        foreach (SourceEnum::cases() as $case) {
            $this->assertIsString($case->value);
            $this->assertNotEmpty($case->value);
        }
    }
}
