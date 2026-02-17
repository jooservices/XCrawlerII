<?php

namespace Modules\JAV\Tests\Feature\Services;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Modules\JAV\Models\Jav;
use Modules\JAV\Services\Clients\OneFourOneJavClient;
use Modules\JAV\Services\Clients\OnejavClient;
use Modules\JAV\Services\CrawlerPaginationStateService;
use Modules\JAV\Services\CrawlerResponseCacheService;
use Modules\JAV\Services\CrawlerStatusPolicyService;
use Modules\JAV\Services\OneFourOneJavService;
use Modules\JAV\Services\OnejavService;
use Modules\JAV\Tests\TestCase;

/**
 * Integration tests: Service (mocked HTTP with fixtures) -> Parse -> Event -> Subscriber -> DB.
 */
class JavServiceTest extends TestCase
{
    use RefreshDatabase;

    private function makeOnejavService(OnejavClient $client): OnejavService
    {
        return new OnejavService(
            $client,
            app(CrawlerResponseCacheService::class),
            app(CrawlerPaginationStateService::class),
            app(CrawlerStatusPolicyService::class)
        );
    }

    private function makeOneFourOneService(OneFourOneJavClient $client): OneFourOneJavService
    {
        return new OneFourOneJavService(
            $client,
            app(CrawlerResponseCacheService::class),
            app(CrawlerPaginationStateService::class),
            app(CrawlerStatusPolicyService::class)
        );
    }

    public function test_onejav_new_stores_items_in_database(): void
    {
        $client = Mockery::mock(OnejavClient::class);
        $client->shouldReceive('get')
            ->with('/new?page=15670')
            ->once()
            ->andReturn($this->getMockResponse('onejav_new_15670.html'));

        $service = $this->makeOnejavService($client);
        $adapter = $service->new(15670);
        $items = $adapter->items();

        $this->assertCount(6, $items->items);

        // Assert all 6 items stored
        $storedCount = Jav::where('source', 'onejav')->count();
        $this->assertEquals(6, $storedCount);

        // Verify data integrity for ABP462
        $jav = Jav::where('code', 'ABP-462')->where('source', 'onejav')->first();
        $this->assertNotNull($jav);
        $this->assertEquals('ABP462', $jav->title);
        $this->assertEquals('/torrent/abp462', $jav->url);
        $this->assertEquals(1.2, $jav->size);
        $this->assertEquals('2016-10-16', $jav->date->format('Y-m-d'));
        $this->assertEquals(['Lingerie', 'Masturbation', 'Pantyhose', 'Solowork', 'Toy'], $jav->tags->pluck('name')->sort()->values()->toArray());
        $this->assertEquals(['Nao Wakana'], $jav->actors->pluck('name')->sort()->values()->toArray());
        $this->assertEquals('/torrent/abp462/download/91625328/onejav.com_abp462.torrent', $jav->download);

        // Verify IPZ725
        $jav = Jav::where('code', 'IPZ-725')->where('source', 'onejav')->first();
        $this->assertNotNull($jav);
        $this->assertEquals(1.1, $jav->size);
        $this->assertEquals(['Arisa Shindo'], $jav->actors->pluck('name')->sort()->values()->toArray());

        // Verify TEK074
        $jav = Jav::where('code', 'TEK-074')->where('source', 'onejav')->first();
        $this->assertNotNull($jav);
        $this->assertEquals(1.4, $jav->size);
        $this->assertEquals(['Miharu Usa'], $jav->actors->pluck('name')->sort()->values()->toArray());

        // Verify remaining items exist
        $this->assertDatabaseHas('jav', ['code' => 'ABP-459', 'source' => 'onejav']);
        $this->assertDatabaseHas('jav', ['code' => 'TEK-075', 'source' => 'onejav']);
        $this->assertDatabaseHas('jav', ['code' => 'SGA-049', 'source' => 'onejav']);
    }

    public function test_onejav_popular_stores_items_in_database(): void
    {
        $client = Mockery::mock(OnejavClient::class);
        $client->shouldReceive('get')
            ->with('/popular/?page=1')
            ->once()
            ->andReturn($this->getMockResponse('onejav_popular.html'));

        $service = $this->makeOnejavService($client);
        $adapter = $service->popular();
        $items = $adapter->items();

        $storedCount = Jav::where('source', 'onejav')->count();
        $this->assertGreaterThan(0, $storedCount);
        $this->assertEquals($items->items->count(), $storedCount);

        // Every stored item must have required fields
        Jav::where('source', 'onejav')->get()->each(function ($jav) {
            $this->assertNotNull($jav->code);
            $this->assertNotNull($jav->url);
            $this->assertNotNull($jav->title);
        });
    }

    public function test_141jav_new_stores_items_in_database(): void
    {
        $client = Mockery::mock(OneFourOneJavClient::class);
        $client->shouldReceive('get')
            ->with('/new?page=1')
            ->once()
            ->andReturn($this->getMockResponse('141jav_new.html'));

        $service = $this->makeOneFourOneService($client);
        $adapter = $service->new();
        $items = $adapter->items();

        $storedCount = Jav::where('source', '141jav')->count();
        $this->assertGreaterThan(0, $storedCount);
        $this->assertEquals($items->items->count(), $storedCount);

        // Verify source is correct for all records
        Jav::where('source', '141jav')->get()->each(function ($jav) {
            $this->assertEquals('141jav', $jav->source);
            $this->assertNotNull($jav->code);
        });
    }

    public function test_141jav_popular_stores_items_in_database(): void
    {
        $client = Mockery::mock(OneFourOneJavClient::class);
        $client->shouldReceive('get')
            ->with('/popular/?page=1')
            ->once()
            ->andReturn($this->getMockResponse('141jav_popular.html'));

        $service = $this->makeOneFourOneService($client);
        $adapter = $service->popular();
        $items = $adapter->items();

        $storedCount = Jav::where('source', '141jav')->count();
        $this->assertGreaterThan(0, $storedCount);
        $this->assertEquals($items->items->count(), $storedCount);
    }

    public function test_duplicate_items_are_updated_not_duplicated(): void
    {
        $client = Mockery::mock(OnejavClient::class);
        $client->shouldReceive('get')
            ->with('/new?page=15670')
            ->andReturn($this->getMockResponse('onejav_new_15670.html'));

        $service = $this->makeOnejavService($client);

        // First parse
        $service->new(15670)->items();
        $firstCount = Jav::where('source', 'onejav')->count();
        $firstRecord = Jav::where('code', 'ABP-462')->where('source', 'onejav')->first();
        $firstId = $firstRecord->id;

        // Second parse (same fixture)
        $service->new(15670)->items();
        $secondCount = Jav::where('source', 'onejav')->count();

        // Count should not change
        $this->assertEquals($firstCount, $secondCount);

        // ABP462 should still be 1 record with same ID
        $this->assertEquals(1, Jav::where('code', 'ABP-462')->where('source', 'onejav')->count());
        $secondRecord = Jav::where('code', 'ABP-462')->where('source', 'onejav')->first();
        $this->assertEquals($firstId, $secondRecord->id);
    }

    public function test_same_code_different_source_creates_separate_records(): void
    {
        // Parse from onejav
        $onejavClient = Mockery::mock(OnejavClient::class);
        $onejavClient->shouldReceive('get')
            ->andReturn($this->getMockResponse('onejav_new_15670.html'));
        $onejavService = $this->makeOnejavService($onejavClient);
        $onejavService->new(15670)->items();

        $onejavCount = Jav::where('source', 'onejav')->count();
        $this->assertGreaterThan(0, $onejavCount);

        // Parse from 141jav
        $jav141Client = Mockery::mock(OneFourOneJavClient::class);
        $jav141Client->shouldReceive('get')
            ->andReturn($this->getMockResponse('141jav_new.html'));
        $jav141Service = $this->makeOneFourOneService($jav141Client);
        $jav141Service->new()->items();

        $jav141Count = Jav::where('source', '141jav')->count();
        $this->assertGreaterThan(0, $jav141Count);

        // Total from both sources should be sum
        $totalCount = Jav::whereIn('source', ['onejav', '141jav'])->count();
        $this->assertEquals($onejavCount + $jav141Count, $totalCount);
    }
}
