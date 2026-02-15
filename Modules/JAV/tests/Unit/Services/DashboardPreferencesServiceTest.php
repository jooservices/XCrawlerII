<?php

namespace Modules\JAV\Tests\Unit\Services;

use Illuminate\Http\Request;
use Modules\JAV\Services\DashboardPreferencesService;
use Modules\JAV\Tests\TestCase;

class DashboardPreferencesServiceTest extends TestCase
{
    public function test_resolve_returns_defaults_for_null_user_and_non_array_preferences(): void
    {
        $service = new DashboardPreferencesService;

        $defaults = $service->resolve(null);
        $this->assertSame(false, $defaults['show_cover']);
        $this->assertSame(false, $defaults['compact_mode']);
        $this->assertSame('detailed', $defaults['text_preference']);
        $this->assertSame([], $defaults['saved_presets']);

        $user = $this->createUser();
        $user->preferences = 'invalid';

        $resolved = $service->resolve($this->asAuthenticatable($user));
        $this->assertSame($defaults, $resolved);
    }

    public function test_resolve_filters_unknown_keys_from_saved_preferences(): void
    {
        config(['jav.show_cover' => true]);
        $service = new DashboardPreferencesService;

        $user = $this->createUser();
        $user->preferences = [
            'show_cover' => false,
            'text_preference' => 'concise',
            'legacy_key' => 'legacy-value',
        ];

        $resolved = $service->resolve($this->asAuthenticatable($user));

        $this->assertSame(false, $resolved['show_cover']);
        $this->assertSame('concise', $resolved['text_preference']);
        $this->assertArrayNotHasKey('legacy_key', $resolved);
    }

    public function test_normalize_tag_filters_merges_tags_array_with_csv_tag(): void
    {
        $service = new DashboardPreferencesService;

        $request = Request::create('/jav/dashboard', 'GET', [
            'tags' => ['Drama', ' Idol ', '', 'Drama'],
            'tag' => 'Action, Drama , Mystery',
        ]);

        $this->assertSame(['Drama', 'Idol', 'Action', 'Mystery'], $service->normalizeTagFilters($request));
    }

    public function test_normalize_bio_filters_uses_array_and_falls_back_to_single_inputs(): void
    {
        $service = new DashboardPreferencesService;

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

    public function test_normalize_tag_values_deduplicates_and_reindexes_values(): void
    {
        $service = new DashboardPreferencesService;

        $normalized = $service->normalizeTagValues([
            '  Drama ',
            '',
            'Action',
            'Drama',
            null,
            '  Mystery  ',
        ]);

        $this->assertSame(['Drama', 'Action', 'Mystery'], $normalized);
    }

    public function test_normalize_bio_filters_preserves_partial_rows_with_null_side(): void
    {
        $service = new DashboardPreferencesService;

        $normalized = $service->normalizeBioFilters([
            ['key' => 'Height', 'value' => ''],
            ['key' => '', 'value' => '170'],
            ['key' => 'Hair Color', 'value' => 'Black'],
        ]);

        $this->assertSame([
            ['key' => 'height', 'value' => null],
            ['key' => null, 'value' => '170'],
            ['key' => 'hair_color', 'value' => 'Black'],
        ], $normalized);
    }
}
