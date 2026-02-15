<?php

namespace Modules\JAV\Tests\Unit\Services;

use App\Models\User;
use Illuminate\Http\Request;
use Modules\JAV\Services\DashboardPreferencesService;
use Modules\JAV\Tests\TestCase;

class DashboardPreferencesServiceTest extends TestCase
{
    public function test_resolve_returns_defaults_for_null_user_and_non_array_preferences(): void
    {
        $service = new DashboardPreferencesService();

        $defaults = $service->resolve(null);
        $this->assertSame(false, $defaults['show_cover']);
        $this->assertSame(false, $defaults['compact_mode']);
        $this->assertSame('detailed', $defaults['text_preference']);
        $this->assertSame([], $defaults['saved_presets']);

        $user = User::factory()->make();
        $user->preferences = 'invalid';

        $resolved = $service->resolve($user);
        $this->assertSame($defaults, $resolved);
    }

    public function test_resolve_filters_unknown_keys_from_saved_preferences(): void
    {
        config(['jav.show_cover' => true]);
        $service = new DashboardPreferencesService();

        $user = User::factory()->make();
        $user->preferences = [
            'show_cover' => false,
            'text_preference' => 'concise',
            'legacy_key' => 'legacy-value',
        ];

        $resolved = $service->resolve($user);

        $this->assertSame(false, $resolved['show_cover']);
        $this->assertSame('concise', $resolved['text_preference']);
        $this->assertArrayNotHasKey('legacy_key', $resolved);
    }

    public function test_normalize_tag_filters_merges_tags_array_with_csv_tag(): void
    {
        $service = new DashboardPreferencesService();

        $request = Request::create('/jav/dashboard', 'GET', [
            'tags' => ['Drama', ' Idol ', '', 'Drama'],
            'tag' => 'Action, Drama , Mystery',
        ]);

        $this->assertSame(['Drama', 'Idol', 'Action', 'Mystery'], $service->normalizeTagFilters($request));
    }

    public function test_normalize_bio_filters_uses_array_and_falls_back_to_single_inputs(): void
    {
        $service = new DashboardPreferencesService();

        $fromArray = $service->normalizeBioFilters([
            ['key' => 'Blood Type', 'value' => ' O '],
            ['key' => '', 'value' => ''],
            ['key' => 'City Of Birth', 'value' => 'Tokyo'],
            'invalid-row',
        ]);

        $this->assertSame([
            ['key' => 'blood_type', 'value' => 'O'],
            ['key' => 'city_of_birth', 'value' => 'Tokyo'],
        ], $fromArray);

        $fallback = $service->normalizeBioFilters([], 'Special Skill', 'Dancing');
        $this->assertSame([
            ['key' => 'special_skill', 'value' => 'Dancing'],
        ], $fallback);
    }
}
