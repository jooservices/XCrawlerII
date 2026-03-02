<?php

declare(strict_types=1);

namespace Modules\Core\Tests\Unit\DTOs\Events;

use Modules\Core\DTOs\Events\ChangeSet;
use Modules\Core\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

final class ChangeSetTest extends TestCase
{
    #[Test]
    public function constructor_stores_properties(): void
    {
        $set = new ChangeSet(
            changedFields: ['name', 'status'],
            previousPartial: ['name' => 'old', 'status' => 1],
            newPartial: ['name' => 'new', 'status' => 2]
        );

        $this->assertSame(['name', 'status'], $set->changedFields);
        $this->assertSame(['name' => 'old', 'status' => 1], $set->previousPartial);
        $this->assertSame(['name' => 'new', 'status' => 2], $set->newPartial);
    }

    #[Test]
    public function has_changes_returns_false_when_changed_fields_empty(): void
    {
        $set = new ChangeSet([], null, null);

        $this->assertFalse($set->hasChanges());
    }

    #[Test]
    public function has_changes_returns_true_when_changed_fields_not_empty(): void
    {
        $set = new ChangeSet(['name'], ['name' => 'a'], ['name' => 'b']);

        $this->assertTrue($set->hasChanges());
    }

    #[Test]
    public function has_changes_returns_true_for_multiple_changed_fields(): void
    {
        $set = new ChangeSet(
            ['a', 'b'],
            ['a' => 1, 'b' => 2],
            ['a' => 10, 'b' => 20]
        );

        $this->assertTrue($set->hasChanges());
    }
}
