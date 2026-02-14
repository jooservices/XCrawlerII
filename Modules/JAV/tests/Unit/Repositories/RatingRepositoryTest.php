<?php

namespace Modules\JAV\Tests\Unit\Repositories;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\JAV\Models\Jav;
use Modules\JAV\Models\Rating;
use Modules\JAV\Repositories\RatingRepository;
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

        $rating1 = Rating::factory()->create(['user_id' => $user->id, 'jav_id' => $jav1->id]);
        Rating::factory()->create(['user_id' => $otherUser->id, 'jav_id' => $jav2->id]);

        $result = app(RatingRepository::class)->keyedByJavIdForUserAndJavIds($user->id, collect([$jav1->id, $jav2->id]));

        $this->assertTrue($result->has($jav1->id));
        $this->assertFalse($result->has($jav2->id));
        $this->assertSame($rating1->id, $result->get($jav1->id)->id);
    }
}
