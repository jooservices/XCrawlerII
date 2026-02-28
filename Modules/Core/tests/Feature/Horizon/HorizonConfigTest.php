<?php

declare(strict_types=1);

namespace Modules\Core\Tests\Feature\Horizon;

use Modules\Core\Enums\Queue\QueueEnum;
use Modules\Core\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

final class HorizonConfigTest extends TestCase
{
    #[Test]
    public function horizon_defaults_supervisor_queue_list_contains_enum_default_value(): void
    {
        $queues = config('horizon.defaults.supervisor-default.queue');

        self::assertIsArray($queues);
        self::assertSame([QueueEnum::DEFAULT->value], $queues);
    }

    #[Test]
    public function horizon_waits_config_references_queue_enum(): void
    {
        $waits = config('horizon.waits');

        self::assertArrayHasKey('redis:'.QueueEnum::DEFAULT->value, $waits);
    }
}
