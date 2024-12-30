<?php

namespace Modules\Jav\Tests\Unit\Events;

use Illuminate\Support\Facades\Event;
use Modules\Jav\Events\JavMovieCreateCompleted;
use Modules\Jav\Events\Onejav\ReferenceCreatedEvent;
use Modules\Jav\Models\JavMovie;
use Modules\Jav\Models\OnejavReference;
use Modules\Jav\tests\TestCase;

class JavMovieCreatedTest extends TestCase
{
    final public function testMovieCreatedEvent(): void
    {
        $onejav = OnejavReference::factory()->create();

        Event::fake([
            JavMovieCreateCompleted::class,
        ]);

        Event::dispatch(
            new ReferenceCreatedEvent($onejav)
        );

        $this->assertDatabaseHas(
            'jav_movies',
            [
                'dvd_id' => $onejav->dvd_id,
            ]
        );

        $movie = JavMovie::where('dvd_id', $onejav->dvd_id)->first();

        $this->assertCount(
            count($movie->performers),
            $onejav->performers
        );
        $this->assertCount(
            count($movie->genres),
            $onejav->genres
        );

        Event::assertDispatched(JavMovieCreateCompleted::class);
    }
}
