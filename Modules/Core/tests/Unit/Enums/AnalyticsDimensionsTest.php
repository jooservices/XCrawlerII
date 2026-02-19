<?php

namespace Modules\Core\Tests\Unit\Enums;

use Modules\Core\Enums\AnalyticsDomain;
use Modules\Core\Enums\AnalyticsEntityType;
use Modules\Core\Tests\TestCase;

class AnalyticsDimensionsTest extends TestCase
{
    public function test_domain_values_are_stable(): void
    {
        $this->assertSame(['jav'], AnalyticsDomain::values());
        $this->assertSame('jav', AnalyticsDomain::Jav->value);
    }

    public function test_entity_type_values_are_stable(): void
    {
        $this->assertSame(['movie', 'actor', 'tag'], AnalyticsEntityType::values());
        $this->assertSame('movie', AnalyticsEntityType::Movie->value);
        $this->assertSame('actor', AnalyticsEntityType::Actor->value);
        $this->assertSame('tag', AnalyticsEntityType::Tag->value);
    }
}
