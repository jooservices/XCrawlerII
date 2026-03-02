<?php

declare(strict_types=1);

namespace Modules\JAV\Tests\Unit\Repositories;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\JAV\Models\Actor;
use Modules\JAV\Repositories\ActorRepository;
use Modules\JAV\Tests\TestCase;

final class ActorRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private ActorRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new ActorRepository();
    }

    public function test_happy_upsert_by_name_creates_new_actor(): void
    {
        $actor = $this->repository->upsertByName('Alice Doe');

        $this->assertInstanceOf(Actor::class, $actor);
        $this->assertSame('Alice Doe', $actor->name);
        $this->assertNotNull($actor->uuid);
        $this->assertDatabaseHas('actors', ['name' => 'Alice Doe']);
    }

    public function test_happy_upsert_by_name_updates_existing(): void
    {
        Actor::factory()->create(['name' => 'Bob Smith']);

        $actor = $this->repository->upsertByName('Bob Smith', ['avatar' => 'https://example.com/avatar.jpg']);

        $this->assertSame('https://example.com/avatar.jpg', $actor->avatar);
    }

    public function test_happy_find_by_name_returns_actor(): void
    {
        Actor::factory()->create(['name' => 'Carol White']);

        $found = $this->repository->findByName('Carol White');

        $this->assertInstanceOf(Actor::class, $found);
        $this->assertSame('Carol White', $found->name);
    }

    public function test_unhappy_find_by_name_returns_null(): void
    {
        $this->assertNull($this->repository->findByName('Nobody'));
    }
}
