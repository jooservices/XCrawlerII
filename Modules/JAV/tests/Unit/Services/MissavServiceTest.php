<?php

namespace Modules\JAV\Tests\Unit\Services;

use Illuminate\Support\Facades\Event;
use Mockery;
use Modules\JAV\Events\ItemParsed;
use Modules\JAV\Models\MissavSchedule;
use Modules\JAV\Services\Clients\MissavBrowserClient;
use Modules\JAV\Services\Missav\ItemsAdapter;
use Modules\JAV\Services\MissavService;
use Modules\JAV\Tests\TestCase;

class MissavServiceTest extends TestCase
{
    public function test_new_enqueues_schedule_items(): void
    {
        Event::fake([ItemParsed::class]);

        $html = $this->loadFixture('missav/missav_new.html');
        $expectedAdapter = new ItemsAdapter($html);
        $expectedCount = $expectedAdapter->items()->items->count();

        $browser = Mockery::mock(MissavBrowserClient::class);
        $browser->shouldReceive('fetchHtml')
            ->once()
            ->with('/dm590/en/release')
            ->andReturn($html);

        $service = new MissavService($browser);
        $service->new(1);

        $this->assertSame($expectedCount, MissavSchedule::count());
        $this->assertSame($expectedCount, MissavSchedule::where('status', 'pending')->count());
    }

    public function test_item_parses_detail(): void
    {
        Event::fake([ItemParsed::class]);

        $detailHtml = $this->loadFixture('missav/missav_item_spsb-038.html');
        $browser = Mockery::mock(MissavBrowserClient::class);
        $browser->shouldReceive('fetchHtml')
            ->once()
            ->with('https://missav.ai/en/spsb-038')
            ->andReturn($detailHtml);

        $service = new MissavService($browser);
        $item = $service->item('https://missav.ai/en/spsb-038');

        $this->assertSame('SPSB-038', $item->code);
        $this->assertSame('https://missav.ai/en/spsb-038', $item->url);
        $this->assertSame('2026-02-16', $item->date?->format('Y-m-d'));
    }
}
