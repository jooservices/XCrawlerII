<?php

namespace Modules\JAV\Tests\Unit\Repositories;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\JAV\Models\Tag;
use Modules\JAV\Repositories\TagRepository;
use Tests\TestCase;

class TagRepositoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_suggestions_returns_trimmed_names(): void
    {
        Tag::factory()->create(['name' => '  Action  ']);
        Tag::factory()->create(['name' => 'Drama']);

        $result = app(TagRepository::class)->suggestions();

        $this->assertContains('Action', $result);
        $this->assertContains('Drama', $result);
    }
}
