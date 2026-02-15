<?php

namespace Modules\JAV\Tests\Feature;

use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AccountSettingsTest extends TestCase
{
    use RefreshDatabase;

    private const OLD_NAME = 'Old Name';

    private const NEW_NAME = 'New Name';

    private const OLD_EMAIL = 'old@example.com';

    private const NEW_EMAIL = 'new@example.com';

    public function test_user_can_upload_custom_avatar(): void
    {
        Storage::fake('public');

        /** @var User $user */
        $user = User::factory()->create();
        /** @var Authenticatable $authUser */
        $authUser = $user;

        $response = $this->actingAs($authUser)->post(route('jav.account.avatar.update'), [
            'avatar' => UploadedFile::fake()->image('avatar.png'),
        ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        $user->refresh();

        $this->assertNotNull($user->avatar_path);
        $this->assertStringStartsWith('avatars/', (string) $user->avatar_path);
        $this->assertTrue(Storage::disk('public')->exists((string) $user->avatar_path));
    }

    public function test_user_can_change_password_with_current_password(): void
    {
        /** @var User $user */
        $user = User::factory()->create([
            'password' => Hash::make('old-password'),
        ]);
        /** @var Authenticatable $authUser */
        $authUser = $user;

        $response = $this->actingAs($authUser)->put(route('jav.account.password.update'), [
            'current_password' => 'old-password',
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        $user->refresh();

        $this->assertTrue(Hash::check('new-password-123', $user->password));
    }

    public function test_user_can_remove_custom_avatar_and_fallback_to_gravatar(): void
    {
        Storage::fake('public');

        $path = UploadedFile::fake()->image('avatar.png')->store('avatars', 'public');

        /** @var User $user */
        $user = User::factory()->create([
            'avatar_path' => $path,
        ]);
        /** @var Authenticatable $authUser */
        $authUser = $user;

        $this->assertTrue(Storage::disk('public')->exists($path));

        $response = $this->actingAs($authUser)->delete(route('jav.account.avatar.remove'));

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        $user->refresh();

        $this->assertNull($user->avatar_path);
        $this->assertFalse(Storage::disk('public')->exists($path));
    }

    public function test_user_can_update_name_and_email(): void
    {
        /** @var User $user */
        $user = User::factory()->create([
            'name' => self::OLD_NAME,
            'email' => self::OLD_EMAIL,
            'password' => Hash::make('current-pass-123'),
        ]);
        /** @var Authenticatable $authUser */
        $authUser = $user;

        $response = $this->actingAs($authUser)->put(route('jav.account.profile.update'), [
            'name' => self::NEW_NAME,
            'email' => self::NEW_EMAIL,
            'current_password' => 'current-pass-123',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        $user->refresh();

        $this->assertSame(self::NEW_NAME, $user->name);
        $this->assertSame(self::NEW_EMAIL, $user->email);
    }

    public function test_user_cannot_change_email_without_current_password(): void
    {
        /** @var User $user */
        $user = User::factory()->create([
            'name' => self::OLD_NAME,
            'email' => self::OLD_EMAIL,
            'password' => Hash::make('current-pass-123'),
        ]);
        /** @var Authenticatable $authUser */
        $authUser = $user;

        $response = $this->actingAs($authUser)->from('/jav/preferences')->put(route('jav.account.profile.update'), [
            'name' => self::NEW_NAME,
            'email' => self::NEW_EMAIL,
            'current_password' => '',
        ]);

        $response->assertRedirect('/jav/preferences');
        $response->assertSessionHasErrors(['current_password']);

        $user->refresh();

        $this->assertSame(self::OLD_EMAIL, $user->email);
    }
}
