<?php

namespace Modules\JAV\Tests\Unit\Repositories;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\JAV\Models\Interaction;
use Modules\JAV\Models\Jav;
use Modules\JAV\Repositories\InteractionRepository;
use Tests\TestCase;

class RatingRepositoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_keyed_by_jav_id_for_user_and_jav_ids_returns_only_matching_user_and_ids(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $jav1 = Jav::factory()->create();
        $jav2 = Jav::factory()->create();

        $rating1 = Interaction::factory()
            ->forJav($jav1)
            ->rating(4)
            ->create(['user_id' => $user->id]);
        Interaction::factory()
            ->forJav($jav2)
            ->rating(3)
            ->create(['user_id' => $otherUser->id]);

        $result = app(InteractionRepository::class)->keyedRatingsForUserAndJavIds($user->id, collect([$jav1->id, $jav2->id]));

        $this->assertTrue($result->has($jav1->id));
        $this->assertFalse($result->has($jav2->id));
        $this->assertSame($rating1->id, $result->get($jav1->id)->id);
    }
}
