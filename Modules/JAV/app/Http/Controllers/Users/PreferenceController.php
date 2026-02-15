<?php

namespace Modules\JAV\Http\Controllers\Users;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Modules\JAV\Http\Requests\DeletePresetRequest;
use Modules\JAV\Http\Requests\SavePreferencesRequest;
use Modules\JAV\Http\Requests\SavePresetRequest;
use Modules\JAV\Http\Requests\UpdateAvatarRequest;
use Modules\JAV\Http\Requests\UpdatePasswordRequest;
use Modules\JAV\Http\Requests\UpdateProfileRequest;
use Modules\JAV\Services\DashboardPreferencesService;

class PreferenceController extends Controller
{
    public function __construct(private readonly DashboardPreferencesService $dashboardPreferencesService) {}

    public function index(): InertiaResponse
    {
        $preferences = $this->dashboardPreferencesService->resolve(Auth::user());

        return Inertia::render('User/Preferences', [
            'preferences' => $preferences,
        ]);
    }

    public function save(SavePreferencesRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $user = $request->user();
        $preferences = $this->dashboardPreferencesService->resolve($user);
        $preferences['show_cover'] = (bool) ($validated['show_cover'] ?? false);
        $preferences['compact_mode'] = (bool) ($validated['compact_mode'] ?? false);
        $preferences['text_preference'] = (string) $validated['text_preference'];
        $user->update(['preferences' => $preferences]);

        return back()->with('success', 'Preferences updated.');
    }

    public function updateAvatar(UpdateAvatarRequest $request): RedirectResponse
    {
        $user = $request->user();

        if (is_string($user->avatar_path) && $user->avatar_path !== '') {
            Storage::disk('public')->delete($user->avatar_path);
        }

        $path = $request->file('avatar')->store('avatars', 'public');

        $user->update([
            'avatar_path' => $path,
        ]);

        return back()->with('success', 'Avatar updated.');
    }

    public function removeAvatar(): RedirectResponse
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            return back()->with('error', 'Unable to resolve authenticated user.');
        }

        if (is_string($user->avatar_path) && $user->avatar_path !== '') {
            Storage::disk('public')->delete($user->avatar_path);
            $user->update([
                'avatar_path' => null,
            ]);
        }

        return back()->with('success', 'Custom avatar removed. Using Gravatar.');
    }

    public function updatePassword(UpdatePasswordRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $user = $request->user();

        $user->update([
            'password' => Hash::make((string) $validated['password']),
        ]);

        return back()->with('success', 'Password updated.');
    }

    public function updateProfile(UpdateProfileRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $user = $request->user();

        $user->update([
            'name' => (string) $validated['name'],
            'email' => (string) $validated['email'],
        ]);

        return back()->with('success', 'Profile updated.');
    }

    public function savePreset(SavePresetRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $user = $request->user();
        $preferences = $this->dashboardPreferencesService->resolve($user);
        $savedPresets = is_array($preferences['saved_presets'] ?? null) ? $preferences['saved_presets'] : [];

        $savedPresets[] = [
            'name' => (string) $validated['name'],
            'query' => (string) ($validated['q'] ?? ''),
            'actor' => (string) ($validated['actor'] ?? ''),
            'tag' => (string) ($validated['tag'] ?? ''),
            'tags' => $this->dashboardPreferencesService->normalizeTagValues($validated['tags'] ?? []),
            'tags_mode' => (string) ($validated['tags_mode'] ?? 'any'),
            'age' => $validated['age'] ?? null,
            'age_min' => $validated['age_min'] ?? null,
            'age_max' => $validated['age_max'] ?? null,
            'bio_key' => (string) ($validated['bio_key'] ?? ''),
            'bio_value' => (string) ($validated['bio_value'] ?? ''),
            'bio_filters' => $this->dashboardPreferencesService->normalizeBioFilters(
                $validated['bio_filters'] ?? [],
                $validated['bio_key'] ?? null,
                $validated['bio_value'] ?? null
            ),
            'sort' => (string) ($validated['sort'] ?? ''),
            'direction' => (string) ($validated['direction'] ?? 'desc'),
            'preset' => (string) ($validated['preset'] ?? 'default'),
        ];

        $preferences['saved_presets'] = array_slice($savedPresets, -10);
        $user->update(['preferences' => $preferences]);

        return back()->with('success', 'Preset saved.');
    }

    public function deletePreset(DeletePresetRequest $request): RedirectResponse
    {
        $presetKey = (int) $request->validated('presetKey');
        $user = $request->user();
        $preferences = $this->dashboardPreferencesService->resolve($user);
        $savedPresets = is_array($preferences['saved_presets'] ?? null) ? $preferences['saved_presets'] : [];

        if (isset($savedPresets[$presetKey])) {
            unset($savedPresets[$presetKey]);
            $preferences['saved_presets'] = array_values($savedPresets);
            $user->update(['preferences' => $preferences]);
        }

        return back()->with('success', 'Preset deleted.');
    }
}
