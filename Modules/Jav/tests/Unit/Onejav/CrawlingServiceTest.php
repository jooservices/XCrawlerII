<?php

namespace Modules\Jav\Tests\Unit\Onejav;

use Illuminate\Support\Facades\Event;
use Modules\Client\Services\ClientManager;
use Modules\Jav\Entities\OnejavItemEntity;
use Modules\Jav\Events\CrawlingFailedEvent;
use Modules\Jav\Events\OnejavHaveNextPageEvent;
use Modules\Jav\Onejav\Client as OnejavClient;
use Modules\Jav\Onejav\CrawlingService;
use Modules\Jav\tests\TestCase;

class CrawlingServiceTest extends TestCase
{
    /**
     * @TODO Testing with parsing cases
     */
    public function testGetItem(): void
    {
        $response = app(ClientManager::class)
            ->getClient(OnejavClient::class)
            ->get('/item');

        $body = $response->parseBody();

        $item = app(CrawlingService::class)
            ->item($body->getData());

        $this->assertInstanceOf(OnejavItemEntity::class, $item);
        $this->assertEquals('/torrent/cvdx588', $item->url);
        $this->assertEquals('https://pics.dmm.co.jp/mono/movie/adult/h_086cvdx588/h_086cvdx588pl.jpg', $item->cover);
        $this->assertEquals('CVDX-588', $item->dvd_id);
        $this->assertEquals(7.0, $item->size);
    }

    public function testGetItemsSuccess()
    {
        Event::fake([
            OnejavHaveNextPageEvent::class,
        ]);

        $result = app(CrawlingService::class)
            ->getItems();

        $this->assertCount(10, $result);
        $this->assertDatabaseHas(
            'settings',
            [
                'group' => 'onejav',
                'key' => 'new_last_page',
                'value' => 4,
            ],
            'mongodb'
        );

        Event::assertDispatched(OnejavHaveNextPageEvent::class, function ($event) {
            return $event->endpoint === 'new'
                && $event->currentPage === 1
                && $event->lastPage === 4;
        });
    }

    public function testGetItemsFailed()
    {
        Event::fake([
            CrawlingFailedEvent::class,
        ]);

        app(CrawlingService::class)
            ->getItems('404');

        Event::assertDispatched(CrawlingFailedEvent::class);
    }
}
