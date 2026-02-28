<?php

declare(strict_types=1);

namespace Modules\Core\Tests\Unit\Services\Events;

use Modules\Core\Facades\ChangeSet;
use Modules\Core\Tests\TestCase;

final class ChangeSetBuilderTest extends TestCase
{
    public function test_build_detects_no_changes_when_identical(): void
    {
        $previous = ['a' => 1, 'b' => 'two'];
        $new = ['a' => 1, 'b' => 'two'];

        $set = ChangeSet::build($previous, $new);

        $this->assertFalse($set->hasChanges());
        $this->assertSame([], $set->changedFields);
        $this->assertNull($set->previousPartial);
        $this->assertNull($set->newPartial);
    }

    public function test_build_detects_changed_fields_strict(): void
    {
        $previous = ['name' => 'Alice', 'status' => 1];
        $new = ['name' => 'Bob', 'status' => 1];

        $set = ChangeSet::build($previous, $new);

        $this->assertTrue($set->hasChanges());
        $this->assertSame(['name'], $set->changedFields);
        $this->assertSame(['name' => 'Alice'], $set->previousPartial);
        $this->assertSame(['name' => 'Bob'], $set->newPartial);
    }

    public function test_build_uses_strict_comparison(): void
    {
        $previous = ['count' => 0];
        $new = ['count' => '0'];

        $set = ChangeSet::build($previous, $new);

        $this->assertTrue($set->hasChanges());
        $this->assertSame(['count'], $set->changedFields);
        $this->assertSame(0, $set->previousPartial['count']);
        $this->assertSame('0', $set->newPartial['count']);
    }

    public function test_build_respects_only_keys_whitelist(): void
    {
        $previous = ['name' => 'A', 'secret' => 'x', 'status' => 1];
        $new = ['name' => 'B', 'secret' => 'y', 'status' => 2];

        $set = ChangeSet::build($previous, $new, ['name', 'status']);

        $this->assertContains('name', $set->changedFields);
        $this->assertContains('status', $set->changedFields);
        $this->assertNotContains('secret', $set->changedFields);
        $this->assertArrayNotHasKey('secret', $set->previousPartial ?? []);
        $this->assertArrayNotHasKey('secret', $set->newPartial ?? []);
    }

    public function test_build_limits_keys_by_max_keys(): void
    {
        $previous = [];
        $new = [];
        for ($i = 0; $i < 150; $i++) {
            $previous["key_$i"] = $i;
            $new["key_$i"] = $i + 1;
        }

        $set = ChangeSet::build($previous, $new, null, 10);

        $this->assertCount(10, $set->changedFields);
        $this->assertCount(10, $set->previousPartial ?? []);
        $this->assertCount(10, $set->newPartial ?? []);
    }

    public function test_build_new_key_only_appears_as_change(): void
    {
        $previous = ['a' => 1];
        $new = ['a' => 1, 'b' => 2];

        $set = ChangeSet::build($previous, $new);

        $this->assertSame(['b'], $set->changedFields);
        $this->assertNull($set->previousPartial);
        $this->assertSame(['b' => 2], $set->newPartial);
    }

    public function test_build_removed_key_appears_as_change(): void
    {
        $previous = ['a' => 1, 'b' => 2];
        $new = ['a' => 1];

        $set = ChangeSet::build($previous, $new);

        $this->assertSame(['b'], $set->changedFields);
        $this->assertSame(['b' => 2], $set->previousPartial);
        $this->assertNull($set->newPartial);
    }

    public function test_build_empty_arrays_no_changes(): void
    {
        $set = ChangeSet::build([], []);

        $this->assertFalse($set->hasChanges());
        $this->assertSame([], $set->changedFields);
    }

    public function test_build_nested_depth_one_only_top_level(): void
    {
        $previous = ['nested' => ['a' => 1]];
        $new = ['nested' => ['a' => 2]];

        $set = ChangeSet::build($previous, $new, null, 100, 1);

        $this->assertTrue($set->hasChanges());
        $this->assertSame(['nested'], $set->changedFields);
        $this->assertSame(['a' => 1], $set->previousPartial['nested'] ?? []);
        $this->assertSame(['a' => 2], $set->newPartial['nested'] ?? []);
    }

    public function test_build_weird_types_compared_strictly(): void
    {
        $previous = ['x' => null, 'y' => false];
        $new = ['x' => '', 'y' => 0];

        $set = ChangeSet::build($previous, $new);

        $this->assertCount(2, $set->changedFields);
        $this->assertContains('x', $set->changedFields);
        $this->assertContains('y', $set->changedFields);
    }

    public function test_build_only_keys_empty_means_no_keys_included(): void
    {
        $previous = ['a' => 1];
        $new = ['a' => 2];

        $set = ChangeSet::build($previous, $new, []);

        $this->assertSame([], $set->changedFields);
    }

    public function test_build_only_keys_null_means_all_keys(): void
    {
        $previous = ['a' => 1, 'b' => 2];
        $new = ['a' => 2, 'b' => 3];

        $set = ChangeSet::build($previous, $new, null);

        $this->assertCount(2, $set->changedFields);
    }
}
