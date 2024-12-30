<?php

namespace Modules\Jav\Tests\Unit\Services\Onejav;

use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Event;
use Mockery\MockInterface;
use Modules\Client\Services\ClientManager;
use Modules\Jav\Client\Onejav\Client as OnejavClient;
use Modules\Jav\Client\Onejav\CrawlingService;
use Modules\Jav\Dto\ItemDto;
use Modules\Jav\Dto\TagDto;
use Modules\Jav\Events\CrawlingFailedEvent;
use Modules\Jav\Events\Onejav\HaveNextPageEvent;
use Modules\Jav\tests\TestCase;

class CrawlingServiceTest extends TestCase
{
    /**
     * @TODO Testing with parsing cases
     */
    final public function testGetItem(): void
    {
        $this->wish->wishItem()->wish();
        $response = app(ClientManager::class)
            ->getClient(OnejavClient::class)
            ->get('item');

        $body = $response->parseBody();

        $item = app(CrawlingService::class)->item($body->getData());

        $this->assertInstanceOf(ItemDto::class, $item);
        $this->assertEquals('/torrent/cvdx588', $item->url);
        $this->assertEquals('https://pics.dmm.co.jp/mono/movie/adult/h_086cvdx588/h_086cvdx588pl.jpg', $item->cover);
        $this->assertEquals('CVDX-588', $item->dvd_id);
        $this->assertEquals(7.0, $item->size);
    }

    final public function testGetItemsSuccess(): void
    {
        Event::fake([
            HaveNextPageEvent::class,
        ]);

        $this->wish->wishNew()->wish();
        $items = app(CrawlingService::class)
            ->getItems();

        $this->assertCount(10, $items->getItems());
        $this->assertDatabaseHas(
            'settings',
            [
                'group' => 'onejav',
                'key' => 'new_last_page',
                'value' => 4,
            ],
            'mongodb'
        );

        Event::assertDispatched(HaveNextPageEvent::class, function ($event) {
            return $event->endpoint === 'new'
                && $event->currentPage === 1
                && $event->lastPage === 4;
        });
    }

    final public function testGetItemsFailed(): void
    {
        Event::fake([
            CrawlingFailedEvent::class,
        ]);

        $this->wish->wish(function (MockInterface $mock) {
            $mock->allows('request')
                ->andReturn(new Response(404));

            return $mock;
        });

        app(CrawlingService::class)
            ->getItems();

        Event::assertDispatched(CrawlingFailedEvent::class);
    }

    final public function testGetTags(): void
    {
        $this->wish->wishTags()->wish();

        $tags = app(CrawlingService::class)->tags();

        $this->assertCount(353, $tags);
        $this->assertInstanceOf(TagDto::class, $tags->first());
    }
}
