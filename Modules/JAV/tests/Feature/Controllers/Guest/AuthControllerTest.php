<?php

namespace Modules\JAV\Tests\Feature\Controllers\Guest;

use App\Models\User;
use Modules\JAV\Tests\Feature\Controllers\Concerns\InteractsWithInertiaPage;
use Modules\JAV\Tests\TestCase;

class AuthControllerTest extends TestCase
{
    use InteractsWithInertiaPage;

    public function test_guest_can_render_vue_auth_pages(): void
    {
        $loginResponse = $this->get(route('jav.vue.login'));
        $this->assertInertiaPage($loginResponse, 'Auth/Login');

        $registerResponse = $this->get(route('jav.vue.register'));
        $this->assertInertiaPage($registerResponse, 'Auth/Register');
    }

    public function test_authenticated_user_is_redirected_from_guest_vue_auth_pages(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('jav.vue.login'))
            ->assertStatus(302);

        $this->actingAs($user)
            ->get(route('jav.vue.register'))
            ->assertStatus(302);
    }
}
