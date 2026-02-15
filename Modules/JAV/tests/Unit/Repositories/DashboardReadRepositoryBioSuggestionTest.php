<?php

namespace Modules\JAV\Tests\Unit\Repositories;

use Illuminate\Support\Facades\Cache;
use Modules\JAV\Models\Actor;
use Modules\JAV\Models\ActorProfileAttribute;
use Modules\JAV\Repositories\DashboardReadRepository;
use Modules\JAV\Tests\TestCase;

class DashboardReadRepositoryBioSuggestionTest extends TestCase
{
    public function test_bio_value_suggestions_merge_profile_attribute_and_legacy_values(): void
    {
        Cache::flush();

        Actor::factory()->create([
            'xcity_blood_type' => 'AB',
            'xcity_city_of_birth' => 'Tokyo',
        ]);

        $actor = Actor::factory()->create([
            'xcity_blood_type' => 'O',
            'xcity_city_of_birth' => 'Osaka',
        ]);

        ActorProfileAttribute::factory()->create([
            'actor_id' => $actor->id,
            'kind' => 'blood_type',
            'value_string' => 'A',
            'value_label' => 'Blood Type',
            'raw_value' => 'A',
        ]);

        $repository = app(DashboardReadRepository::class);
        $suggestions = $repository->bioValueSuggestions([
            'blood_type' => 'Blood Type',
            'city_of_birth' => 'City Of Birth',
        ]);

        $this->assertArrayHasKey('blood_type', $suggestions);
        $this->assertArrayHasKey('city_of_birth', $suggestions);

        $this->assertContains('A', $suggestions['blood_type']);
        $this->assertContains('AB', $suggestions['blood_type']);
        $this->assertContains('O', $suggestions['blood_type']);

        $this->assertContains('Tokyo', $suggestions['city_of_birth']);
        $this->assertContains('Osaka', $suggestions['city_of_birth']);
    }
}
