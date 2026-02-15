<?php

namespace Modules\JAV\Tests\Feature\Controllers\Users\Api;

use App\Models\User;
use Modules\JAV\Models\Jav;
use Modules\JAV\Models\UserLikeNotification;
use Modules\JAV\Tests\TestCase;

class NotificationControllerContractTest extends TestCase
{
    public function test_notifications_api_requires_auth_and_enforces_ownership(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $jav = Jav::factory()->create();

        $mineUnread = UserLikeNotification::query()->create([
            'user_id' => $user->id,
            'jav_id' => $jav->id,
            'dedupe_key' => "user:{$user->id}|jav:{$jav->id}|type:like_match",
            'title' => 'Mine',
            'message' => 'Message',
            'payload' => [],
        ]);

        $otherUnread = UserLikeNotification::query()->create([
            'user_id' => $otherUser->id,
            'jav_id' => $jav->id,
            'dedupe_key' => "user:{$otherUser->id}|jav:{$jav->id}|type:like_match",
            'title' => 'Other',
            'message' => 'Message',
            'payload' => [],
        ]);

        $this->getJson(route('jav.api.notifications.index'))->assertUnauthorized();

        $this->actingAs($user)
            ->getJson(route('jav.api.notifications.index'))
            ->assertOk()
            ->assertJsonStructure(['success', 'count', 'items'])
            ->assertJsonPath('success', true)
            ->assertJsonPath('count', 1);

        $this->actingAs($user)
            ->postJson(route('jav.api.notifications.read', $mineUnread))
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertNotNull($mineUnread->fresh()->read_at);

        $this->actingAs($user)
            ->postJson(route('jav.api.notifications.read', $otherUnread))
            ->assertForbidden();

        $this->actingAs($user)
            ->postJson(route('jav.api.notifications.read-all'))
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertNull($otherUnread->fresh()->read_at);
    }

    public function test_notifications_weird_case_mark_read_is_idempotent_for_same_notification(): void
    {
        $user = User::factory()->create();
        $jav = Jav::factory()->create();

        $notification = UserLikeNotification::query()->create([
            'user_id' => $user->id,
            'jav_id' => $jav->id,
            'dedupe_key' => "user:{$user->id}|jav:{$jav->id}|type:like_match:idempotent",
            'title' => 'Mine',
            'message' => 'Message',
            'payload' => [],
        ]);

        $this->actingAs($user)
            ->postJson(route('jav.api.notifications.read', $notification))
            ->assertOk()
            ->assertJsonPath('success', true);

        $firstReadAt = $notification->fresh()->read_at;
        $this->assertNotNull($firstReadAt);

        $this->actingAs($user)
            ->postJson(route('jav.api.notifications.read', $notification))
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertEquals($firstReadAt?->toISOString(), $notification->fresh()->read_at?->toISOString());
    }

    public function test_notifications_exploit_case_does_not_mark_other_users_records_on_read_all(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $jav = Jav::factory()->create();

        $myUnread = UserLikeNotification::query()->create([
            'user_id' => $user->id,
            'jav_id' => $jav->id,
            'dedupe_key' => "user:{$user->id}|jav:{$jav->id}|type:like_match:mine",
            'title' => 'Mine',
            'message' => 'Message',
            'payload' => [],
        ]);

        $otherUnread = UserLikeNotification::query()->create([
            'user_id' => $otherUser->id,
            'jav_id' => $jav->id,
            'dedupe_key' => "user:{$otherUser->id}|jav:{$jav->id}|type:like_match:other",
            'title' => 'Other',
            'message' => 'Message',
            'payload' => [],
        ]);

        $this->actingAs($user)
            ->postJson(route('jav.api.notifications.read-all'))
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertNotNull($myUnread->fresh()->read_at);
        $this->assertNull($otherUnread->fresh()->read_at);
    }
}
