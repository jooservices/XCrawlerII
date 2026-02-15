<?php

namespace Modules\JAV\Tests\Feature\Controllers\Users;

use Inertia\Testing\AssertableInertia as Assert;
use Modules\JAV\Models\Jav;
use Modules\JAV\Tests\TestCase;

class PreferenceControllerTest extends TestCase
{
    public function test_user_can_save_preferences(): void
    {
        $user = $this->createUser([
            'preferences' => [
                'show_cover' => false,
                'compact_mode' => false,
                'text_preference' => 'detailed',
                'saved_presets' => [],
            ],
        ]);

        $this->actingAs($this->asAuthenticatable($user))
            ->post(route('jav.preferences.save'), [
                'show_cover' => true,
                'compact_mode' => true,
                'text_preference' => 'concise',
            ])
            ->assertRedirect()
            ->assertSessionHas('success', 'Preferences updated.');

        $updated = $user->fresh();
        $this->assertSame(true, $updated->preferences['show_cover']);
        $this->assertSame(true, $updated->preferences['compact_mode']);
        $this->assertSame('concise', $updated->preferences['text_preference']);
    }

    public function test_save_preferences_validates_required_fields(): void
    {
        $user = $this->createUser();

        $this->actingAs($this->asAuthenticatable($user))
            ->post(route('jav.preferences.save'), [
                'text_preference' => 'invalid',
            ])
            ->assertSessionHasErrors(['text_preference']);
    }

    public function test_save_preferences_validates_show_cover_is_boolean(): void
    {
        $user = $this->createUser();

        $this->actingAs($this->asAuthenticatable($user))
            ->post(route('jav.preferences.save'), [
                'show_cover' => 'not-a-boolean',
                'compact_mode' => true,
                'text_preference' => 'detailed',
            ])
            ->assertSessionHasErrors(['show_cover']);
    }

    public function test_guest_cannot_save_preferences(): void
    {
        $this->post(route('jav.preferences.save'), [
            'show_cover' => true,
            'compact_mode' => true,
            'text_preference' => 'concise',
        ])->assertRedirect(route('login'));
    }

    public function test_save_preset_rejects_overlong_name_payload(): void
    {
        $user = $this->createUser();

        $this->actingAs($this->asAuthenticatable($user))
            ->post(route('jav.presets.save'), [
                'name' => str_repeat('A', 61),
                'q' => 'query',
            ])
            ->assertSessionHasErrors(['name']);
    }

    public function test_save_preferences_does_not_remove_saved_presets(): void
    {
        $existingPresets = [
            [
                'name' => 'Keep Me',
                'query' => 'Alpha',
                'actor' => '',
                'tag' => '',
                'tags' => [],
                'tags_mode' => 'any',
                'age' => null,
                'age_min' => null,
                'age_max' => null,
                'bio_key' => '',
                'bio_value' => '',
                'bio_filters' => [],
                'sort' => 'created_at',
                'direction' => 'desc',
                'preset' => 'default',
            ],
        ];
        $user = $this->createUser([
            'preferences' => [
                'saved_presets' => $existingPresets,
            ],
        ]);

        $this->actingAs($this->asAuthenticatable($user))
            ->post(route('jav.preferences.save'), [
                'show_cover' => true,
                'compact_mode' => true,
                'text_preference' => 'concise',
            ])
            ->assertRedirect()
            ->assertSessionHas('success', 'Preferences updated.');

        $updated = $user->fresh();
        $actualPresets = $updated->preferences['saved_presets'] ?? [];

        $normalize = static function (array $preset): array {
            ksort($preset);

            return $preset;
        };

        $this->assertEquals(
            array_map($normalize, $existingPresets),
            array_map($normalize, $actualPresets)
        );
    }

    public function test_user_can_save_preset_and_presets_are_trimmed_to_latest_ten(): void
    {
        $existingPresets = [];
        for ($i = 0; $i < 10; $i++) {
            $existingPresets[] = [
                'name' => 'Preset '.$i,
                'query' => 'Q'.$i,
                'actor' => '',
                'tag' => '',
                'tags' => [],
                'tags_mode' => 'any',
                'age' => null,
                'age_min' => null,
                'age_max' => null,
                'bio_key' => '',
                'bio_value' => '',
                'bio_filters' => [],
                'sort' => 'views',
                'direction' => 'desc',
                'preset' => 'default',
            ];
        }

        $user = $this->createUser([
            'preferences' => [
                'saved_presets' => $existingPresets,
            ],
        ]);

        $this->actingAs($this->asAuthenticatable($user))
            ->post(route('jav.presets.save'), [
                'name' => 'Newest Preset',
                'q' => 'new query',
                'actor' => 'Alice',
                'tag' => 'Drama, Idol',
                'tags' => ['Drama', ' Idol ', '', 'Drama'],
                'tags_mode' => 'all',
                'age_min' => 20,
                'age_max' => 30,
                'bio_key' => 'Blood Type',
                'bio_value' => 'O',
                'sort' => 'downloads',
                'direction' => 'asc',
                'preset' => 'weekly_downloads',
            ])
            ->assertRedirect()
            ->assertSessionHas('success', 'Preset saved.');

        $updated = $user->fresh();
        $savedPresets = $updated->preferences['saved_presets'] ?? [];

        $this->assertCount(10, $savedPresets);
        $this->assertSame('Preset 1', $savedPresets[0]['name']);

        $latest = $savedPresets[9];
        $this->assertSame('Newest Preset', $latest['name']);
        $this->assertSame('new query', $latest['query']);
        $this->assertSame(['Drama', 'Idol'], $latest['tags']);
        $this->assertSame('all', $latest['tags_mode']);
        $this->assertSame('blood_type', $latest['bio_filters'][0]['key']);
        $this->assertSame('O', $latest['bio_filters'][0]['value']);
    }

    public function test_user_can_delete_existing_preset_and_missing_index_is_noop(): void
    {
        $user = $this->createUser([
            'preferences' => [
                'saved_presets' => [
                    ['name' => 'A'],
                    ['name' => 'B'],
                    ['name' => 'C'],
                ],
            ],
        ]);

        $this->actingAs($this->asAuthenticatable($user))
            ->delete(route('jav.presets.delete', ['presetKey' => 1]))
            ->assertRedirect()
            ->assertSessionHas('success', 'Preset deleted.');

        $this->assertSame(['A', 'C'], array_map(static fn (array $preset): string => $preset['name'], $user->fresh()->preferences['saved_presets']));

        $this->actingAs($this->asAuthenticatable($user))
            ->delete(route('jav.presets.delete', ['presetKey' => 99]))
            ->assertRedirect()
            ->assertSessionHas('success', 'Preset deleted.');

        $this->assertSame(['A', 'C'], array_map(static fn (array $preset): string => $preset['name'], $user->fresh()->preferences['saved_presets']));
    }

    public function test_dashboard_applies_saved_preset_when_saved_preset_query_param_is_present(): void
    {
        config(['scout.driver' => 'collection']);

        $javA = Jav::factory()->create(['title' => 'Match A']);
        $javB = Jav::factory()->create(['title' => 'Other B']);

        $user = $this->createUser([
            'preferences' => [
                'saved_presets' => [
                    [
                        'name' => 'Preset One',
                        'query' => 'Match',
                        'actor' => '',
                        'tag' => '',
                        'tags' => [],
                        'tags_mode' => 'any',
                        'age' => null,
                        'age_min' => null,
                        'age_max' => null,
                        'bio_key' => '',
                        'bio_value' => '',
                        'bio_filters' => [],
                        'sort' => 'created_at',
                        'direction' => 'desc',
                        'preset' => 'default',
                    ],
                ],
            ],
        ]);

        $this->actingAs($this->asAuthenticatable($user))
            ->get(route('jav.vue.dashboard', ['saved_preset' => 0]))
            ->assertOk()
            ->assertInertia(fn (Assert $page): Assert => $page
                ->component('Dashboard/Index', false)
                ->where('query', 'Match')
                ->where('savedPresetIndex', 0)
                ->where('items.data.0.uuid', $javA->uuid)
                ->has('items.data', 1)
            );

        $this->assertNotSame($javA->uuid, $javB->uuid);
    }
}
