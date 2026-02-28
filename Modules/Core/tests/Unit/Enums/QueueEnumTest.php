<?php

declare(strict_types=1);

namespace Modules\Core\Tests\Unit\Enums;

use Modules\Core\Enums\Queue\QueueEnum;
use Modules\Core\Tests\Fixtures\Jobs\TestJob;
use Modules\Core\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

final class QueueEnumTest extends TestCase
{
    #[Test]
    public function resolve_unknown_job_class_returns_default(): void
    {
        $result = QueueEnum::resolve('Modules\\Core\\Tests\\Fixtures\\Jobs\\NonExistentJob');

        self::assertSame(QueueEnum::DEFAULT, $result);
        self::assertSame('default', $result->value);
    }

    #[Test]
    public function resolve_unregistered_known_class_returns_default(): void
    {
        $result = QueueEnum::resolve(TestJob::class);

        self::assertSame(QueueEnum::DEFAULT, $result);
    }
}
