<?php

namespace Modules\JAV\Tests\Unit\Services;

use Carbon\Carbon;
use Modules\JAV\Models\Actor;
use Modules\JAV\Models\ActorProfileAttribute;
use Modules\JAV\Models\Jav;
use Modules\JAV\Models\Tag;
use Modules\JAV\Services\SearchService;
use Modules\JAV\Tests\TestCase;

class SearchServiceUnitTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['scout.driver' => 'collection']);
        Jav::disableSearchSyncing();
        Actor::disableSearchSyncing();
        Tag::disableSearchSyncing();
    }

    public function test_search_jav_supports_tag_all_mode_and_actor_csv_filter(): void
    {
        $tagA = Tag::factory()->create(['name' => 'TagA']);
        $tagB = Tag::factory()->create(['name' => 'TagB']);

        $actorAlice = Actor::factory()->create(['name' => 'Alice']);
        $actorBob = Actor::factory()->create(['name' => 'Bob']);

        $matching = Jav::factory()->create(['title' => 'Match']);
        $matching->tags()->attach([$tagA->id, $tagB->id]);
        $matching->actors()->attach($actorAlice->id);

        $missingTag = Jav::factory()->create(['title' => 'MissingTag']);
        $missingTag->tags()->attach([$tagA->id]);
        $missingTag->actors()->attach($actorAlice->id);

        $missingActor = Jav::factory()->create(['title' => 'MissingActor']);
        $missingActor->tags()->attach([$tagA->id, $tagB->id]);
        $missingActor->actors()->attach($actorBob->id);

        $service = app(SearchService::class);
        $results = $service->searchJav('', [
            'tag' => 'TagA,TagB',
            'actor' => 'Alice',
            'tags_mode' => 'all',
        ], 30, 'created_at', 'desc');

        $ids = collect($results->items())->pluck('id')->all();
        $this->assertSame([$matching->id], $ids);
    }

    public function test_search_jav_swaps_age_min_and_age_max_when_reversed(): void
    {
        $actorInRange = Actor::factory()->create([
            'name' => 'InRange',
            'xcity_birth_date' => Carbon::today()->subYears(25)->toDateString(),
        ]);
        $actorOutOfRange = Actor::factory()->create([
            'name' => 'OutOfRange',
            'xcity_birth_date' => Carbon::today()->subYears(45)->toDateString(),
        ]);

        $javInRange = Jav::factory()->create(['title' => 'InRange Movie']);
        $javInRange->actors()->attach($actorInRange->id);

        $javOutOfRange = Jav::factory()->create(['title' => 'OutOfRange Movie']);
        $javOutOfRange->actors()->attach($actorOutOfRange->id);

        $service = app(SearchService::class);
        $results = $service->searchJav('', [
            'age_min' => 40,
            'age_max' => 20,
        ], 30, 'created_at', 'desc');

        $ids = collect($results->items())->pluck('id')->all();
        $this->assertContains($javInRange->id, $ids);
        $this->assertNotContains($javOutOfRange->id, $ids);
    }

    public function test_search_actors_filters_by_age_range_with_legacy_birth_date(): void
    {
        $inRange = Actor::factory()->create([
            'name' => 'Age Match',
            'xcity_birth_date' => Carbon::today()->subYears(25)->toDateString(),
        ]);

        $tooYoung = Actor::factory()->create([
            'name' => 'Age Young',
            'xcity_birth_date' => Carbon::today()->subYears(19)->toDateString(),
        ]);

        $tooOld = Actor::factory()->create([
            'name' => 'Age Old',
            'xcity_birth_date' => Carbon::today()->subYears(40)->toDateString(),
        ]);

        $service = app(SearchService::class);
        $results = $service->searchActors('', [
            'age_min' => 21,
            'age_max' => 30,
        ], 60, 'name', 'asc');

        $ids = collect($results->items())->pluck('id')->all();
        $this->assertContains($inRange->id, $ids);
        $this->assertNotContains($tooYoung->id, $ids);
        $this->assertNotContains($tooOld->id, $ids);
    }

    public function test_search_actors_filters_by_bio_filters_using_profile_attributes_and_legacy_fields(): void
    {
        $matchProfile = Actor::factory()->create(['name' => 'Profile Match']);
        ActorProfileAttribute::factory()->create([
            'actor_id' => $matchProfile->id,
            'kind' => 'blood_type',
            'value_string' => 'O',
            'raw_value' => 'O',
            'value_label' => 'Blood Type',
        ]);

        $matchLegacy = Actor::factory()->create([
            'name' => 'Legacy Match',
            'xcity_blood_type' => 'O',
        ]);

        $noMatch = Actor::factory()->create([
            'name' => 'No Match',
            'xcity_blood_type' => 'AB',
        ]);

        $service = app(SearchService::class);
        $results = $service->searchActors('', [
            'bio_filters' => [
                ['key' => 'blood_type', 'value' => 'O'],
            ],
        ], 60, 'name', 'asc');

        $ids = collect($results->items())->pluck('id')->all();
        $this->assertContains($matchProfile->id, $ids);
        $this->assertContains($matchLegacy->id, $ids);
        $this->assertNotContains($noMatch->id, $ids);
    }
}
