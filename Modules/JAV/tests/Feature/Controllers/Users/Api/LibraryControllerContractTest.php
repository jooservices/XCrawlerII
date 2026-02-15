<?php

namespace Modules\JAV\Tests\Feature\Controllers\Users\Api;

use App\Models\User;
use Modules\JAV\Models\Actor;
use Modules\JAV\Models\Jav;
use Modules\JAV\Models\Tag;
use Modules\JAV\Tests\TestCase;

class LibraryControllerContractTest extends TestCase
{
    public function test_toggle_like_requires_auth_validation_and_toggles_state(): void
    {
        $jav = Jav::factory()->create();

        $this->postJson(route('jav.toggle-like'), [
            'id' => $jav->id,
            'type' => 'jav',
        ])->assertUnauthorized();

        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson(route('jav.toggle-like'), [
                'id' => $jav->id,
                'type' => 'invalid',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['type']);

        $this->actingAs($user)
            ->postJson(route('jav.toggle-like'), [
                'id' => $jav->id,
                'type' => 'jav',
            ])
            ->assertOk()
            ->assertJsonStructure(['success', 'liked'])
            ->assertJsonPath('success', true)
            ->assertJsonPath('liked', true);

        $this->actingAs($user)
            ->postJson(route('jav.toggle-like'), [
                'id' => $jav->id,
                'type' => 'jav',
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('liked', false);

        $actor = Actor::factory()->create();
        $tag = Tag::factory()->create();

        $this->actingAs($user)
            ->postJson(route('jav.toggle-like'), ['id' => $actor->id, 'type' => 'actor'])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->actingAs($user)
            ->postJson(route('jav.toggle-like'), ['id' => $tag->id, 'type' => 'tag'])
            ->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_toggle_like_weird_case_accepts_numeric_id_passed_as_string(): void
    {
        $jav = Jav::factory()->create();
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson(route('jav.toggle-like'), [
                'id' => (string) $jav->id,
                'type' => 'jav',
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('liked', true);
    }

    public function test_toggle_like_exploit_case_rejects_type_injection_attempt(): void
    {
        $jav = Jav::factory()->create();
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson(route('jav.toggle-like'), [
                'id' => $jav->id,
                'type' => 'jav OR 1=1',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['type']);
    }
}
