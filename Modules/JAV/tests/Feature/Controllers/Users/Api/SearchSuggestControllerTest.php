<?php

namespace Modules\JAV\Tests\Feature\Controllers\Users\Api;

use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Modules\JAV\Models\Actor;
use Modules\JAV\Models\Jav;
use Modules\JAV\Models\Tag;
use Modules\JAV\Tests\TestCase;

class SearchSuggestControllerTest extends TestCase
{
    public function test_search_suggest_security_requires_authenticated_user(): void
    {
        $this->getJson(route('jav.api.search.suggest', ['q' => 'Alpha']))
            ->assertUnauthorized();
    }

    public function test_search_suggest_returns_mixed_suggestions_for_authenticated_user(): void
    {
        $user = User::factory()->create();
        assert($user instanceof User);
        assert($user instanceof Authenticatable);
        Jav::factory()->create([
            'code' => 'ABP-123',
            'title' => 'Alpha Search Movie',
        ]);
        Actor::factory()->create(['name' => 'Alpha Actress']);
        Tag::factory()->create(['name' => 'Alpha Tag']);

        $response = $this->actingAs($user)
            ->getJson(route('jav.api.search.suggest', ['q' => 'Alpha', 'limit' => 8]));

        $response
            ->assertOk()
            ->assertJsonStructure([
                'query',
                'suggestions' => [
                    '*' => ['type', 'label', 'href'],
                ],
            ]);

        $suggestions = collect($response->json('suggestions', []));

        $this->assertTrue($suggestions->contains(static fn (array $item): bool => $item['type'] === 'movie'));
        $this->assertTrue($suggestions->contains(static fn (array $item): bool => $item['type'] === 'actor'));
        $this->assertTrue($suggestions->contains(static fn (array $item): bool => $item['type'] === 'tag'));
    }

    public function test_search_suggest_requires_query_with_minimum_two_characters(): void
    {
        $user = User::factory()->create();
        assert($user instanceof User);
        assert($user instanceof Authenticatable);

        $this->actingAs($user)
            ->getJson(route('jav.api.search.suggest', ['q' => 'a']))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['q']);
    }

    public function test_search_suggest_weird_case_trims_query_and_still_finds_results(): void
    {
        $user = User::factory()->create();
        assert($user instanceof User);
        assert($user instanceof Authenticatable);

        Actor::factory()->create(['name' => 'Delta Actor']);

        $response = $this->actingAs($user)
            ->getJson(route('jav.api.search.suggest', ['q' => '   Delta   ']));

        $response->assertOk();
        $this->assertSame('Delta', $response->json('query'));

        $suggestions = collect($response->json('suggestions', []));
        $this->assertTrue($suggestions->contains(static fn (array $item): bool => $item['type'] === 'actor' && $item['label'] === 'Delta Actor'));
    }

    public function test_search_suggest_exploit_case_escapes_sql_wildcards_in_query(): void
    {
        $user = User::factory()->create();
        assert($user instanceof User);
        assert($user instanceof Authenticatable);

        Jav::factory()->create([
            'code' => 'ABP-999',
            'title' => 'Wildcard Movie',
        ]);

        $response = $this->actingAs($user)
            ->getJson(route('jav.api.search.suggest', ['q' => '%%']));

        $response->assertOk();
        $this->assertSame('%%', $response->json('query'));
        $this->assertCount(0, $response->json('suggestions', []));
    }

    public function test_search_suggest_exploit_case_caps_result_count_even_with_large_limit(): void
    {
        $user = User::factory()->create();
        assert($user instanceof User);
        assert($user instanceof Authenticatable);

        for ($i = 1; $i <= 30; $i++) {
            Tag::factory()->create(['name' => 'AlphaTag '.$i]);
        }

        $response = $this->actingAs($user)
            ->getJson(route('jav.api.search.suggest', ['q' => 'AlphaTag', 'limit' => 999]));

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['limit']);
    }
}
