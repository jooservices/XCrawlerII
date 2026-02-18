<?php

namespace Modules\JAV\Tests\Unit\Services;

use App\Models\User;
use Illuminate\Support\Facades\Event;
use Modules\JAV\Events\UserLikeMatchedJav;
use Modules\JAV\Models\Actor;
use Modules\JAV\Models\Interaction;
use Modules\JAV\Models\Jav;
use Modules\JAV\Models\Tag;
use Modules\JAV\Models\UserLikeNotification;
use Modules\JAV\Services\UserLikeNotificationService;
use Modules\JAV\Tests\TestCase;

class UserLikeNotificationServiceTest extends TestCase
{
    public function test_it_creates_one_notification_per_user_for_actor_or_tag_matches(): void
    {
        Event::fake([UserLikeMatchedJav::class]);

        $user = User::factory()->create();
        $actor = Actor::query()->create(['name' => 'Actor A']);
        $tag = Tag::query()->create(['name' => 'Tag A']);
        $jav = Jav::factory()->create(['code' => 'ABP-111', 'source' => 'onejav']);

        $jav->actors()->sync([$actor->id]);
        $jav->tags()->sync([$tag->id]);

        Interaction::factory()->forActor($actor)->favorite()->create(['user_id' => $user->id]);
        Interaction::factory()->forTag($tag)->favorite()->create(['user_id' => $user->id]);

        $created = app(UserLikeNotificationService::class)->notifyForJav($jav);

        $this->assertSame(1, $created);
        $this->assertDatabaseCount('user_like_notifications', 1);

        $notification = UserLikeNotification::query()->first();
        $this->assertSame($user->id, $notification->user_id);
        $this->assertSame($jav->id, $notification->jav_id);
        $this->assertSame(['Actor A'], $notification->payload['matched_actors'] ?? []);
        $this->assertSame(['Tag A'], $notification->payload['matched_tags'] ?? []);

        Event::assertDispatched(UserLikeMatchedJav::class, 1);
    }

    public function test_it_never_creates_duplicate_notification_for_same_user_and_jav(): void
    {
        Event::fake([UserLikeMatchedJav::class]);

        $user = User::factory()->create();
        $actor = Actor::query()->create(['name' => 'Actor A']);
        $jav = Jav::factory()->create(['code' => 'ABP-222', 'source' => 'onejav']);
        $jav->actors()->sync([$actor->id]);

        Interaction::factory()->forActor($actor)->favorite()->create(['user_id' => $user->id]);

        $service = app(UserLikeNotificationService::class);

        $firstCreated = $service->notifyForJav($jav);
        $secondCreated = $service->notifyForJav($jav);

        $this->assertSame(1, $firstCreated);
        $this->assertSame(0, $secondCreated);
        $this->assertDatabaseCount('user_like_notifications', 1);
        Event::assertDispatched(UserLikeMatchedJav::class, 1);
    }
}
