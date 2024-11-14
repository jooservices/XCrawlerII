<?php

namespace Modules\Jav\Tests\Unit\Events;

use Illuminate\Support\Facades\Event;
use Modules\Jav\Events\JavMovieCreateCompleted;
use Modules\Jav\Events\OnejavReferenceCreatedEvent;
use Modules\Jav\Models\JavMovie;
use Modules\Jav\Models\OnejavReference;
use Modules\Jav\tests\TestCase;

class JavMovieCreatedTest extends TestCase
{
    public function testMovieCreatedEvent()
    {
        $onejav = OnejavReference::factory()->create();

        Event::fake([
            JavMovieCreateCompleted::class,
        ]);

        Event::dispatch(
            new OnejavReferenceCreatedEvent($onejav)
        );

        $this->assertDatabaseHas(
            'jav_movies',
            [
                'dvd_id' => $onejav->dvd_id,
            ]
        );

        $movie = JavMovie::where('dvd_id', $onejav->dvd_id)->first();

        $this->assertEquals(
            count($movie->performers),
            count($onejav->performers)
        );
        $this->assertEquals(
            count($movie->genres),
            count($onejav->genres)
        );

        Event::assertDispatched(JavMovieCreateCompleted::class);
    }
}
