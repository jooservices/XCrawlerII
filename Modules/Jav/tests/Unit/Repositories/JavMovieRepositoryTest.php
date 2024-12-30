<?php

namespace Modules\Jav\Tests\Unit\Repositories;

use Modules\Jav\Models\OnejavReference;
use Modules\Jav\Repositories\JavMovieRepository;
use Modules\Jav\tests\TestCase;

class JavMovieRepositoryTest extends TestCase
{
    final public function testCreate(): void
    {
        $model = OnejavReference::factory()->create([
            'genres' => [
                'gener-1',
                'gener-2',
            ],
        ]);

        $repository = app(JavMovieRepository::class);
        $movie = $repository->create($model);

        $this->assertDatabaseHas('jav_genres', ['name' => 'gener-1']);
        $this->assertDatabaseHas('jav_genres', ['name' => 'gener-2']);
        $this->assertCount(2, $movie->genres);
        $this->assertDatabaseCount('jav_genres', 2);
        $this->assertEquals('gener-1', $movie->genres->first()->name);
        $this->assertEquals('gener-2', $movie->genres->last()->name);

        $model = OnejavReference::factory()->create([
            'genres' => [
                'gener-1',
                'gener-2',
            ],
        ]);
        $movie = $repository->create($model);

        $this->assertCount(2, $movie->genres);
        $this->assertDatabaseCount('jav_genres', 2);
        $this->assertEquals('gener-1', $movie->genres->first()->name);
        $this->assertEquals('gener-2', $movie->genres->last()->name);
    }
}
