<?php

namespace Modules\Core\Tests\Unit\Enums;

use Modules\Core\Enums\AnalyticsAction;
use PHPUnit\Framework\TestCase;

class AnalyticsActionTest extends TestCase
{
    public function test_enum_values(): void
    {
        $this->assertSame('view', AnalyticsAction::View->value);
        $this->assertSame('download', AnalyticsAction::Download->value);
        $this->assertSame(['view', 'download'], AnalyticsAction::values());
    }

    public function test_from_valid_string(): void
    {
        $this->assertSame(AnalyticsAction::View, AnalyticsAction::from('view'));
        $this->assertSame(AnalyticsAction::Download, AnalyticsAction::from('download'));
    }

    public function test_try_from_invalid_string_returns_null(): void
    {
        $this->assertNull(AnalyticsAction::tryFrom('invalid'));
    }
}
