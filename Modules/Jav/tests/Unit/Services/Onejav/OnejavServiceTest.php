<?php

namespace Modules\Jav\Tests\Unit\Services\Onejav;

use Carbon\Carbon;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Modules\Jav\Client\Onejav\CrawlingService;
use Modules\Jav\Events\Onejav\HaveNextPageEvent;
use Modules\Jav\Jobs\Onejav\FetchItemsJob;
use Modules\Jav\Services\Onejav\OnejavService;
use Modules\Jav\tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class OnejavServiceTest extends TestCase
{
    final public function testCrawl(): void
    {
        Event::fake([
            HaveNextPageEvent::class,
        ]);

        $this->wish->wishNew()->wish();

        $service = app(OnejavService::class);
        $items = $service->crawl('new');

        $this->assertSetting(OnejavService::SETTING_GROUP, 'new_current_page', 2);
        $this->assertCount(10, $items->getItems());

        Event::assertDispatched(HaveNextPageEvent::class);
    }

    final public function testCrawlAtEndOfPages(): void
    {
        Event::fake([
            HaveNextPageEvent::class,
        ]);

        $this->wish->wishNew()->wish();

        $service = app(OnejavService::class);
        $items = $service->crawl('new', 4);

        $this->assertSetting(OnejavService::SETTING_GROUP, 'new_current_page', 1);
        $this->assertCount(10, $items->getItems());

        Event::assertNotDispatched(HaveNextPageEvent::class);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    #[DataProvider('functions')]
    final public function testWith(string $with): void
    {
        Queue::fake([
            FetchItemsJob::class,
        ]);

        $this->wish
            ->wishNew()
            ->wishPopular()
            ->wish();

        $service = app(OnejavService::class);
        $service->{$with}();

        Queue::assertPushed(FetchItemsJob::class, function ($job) use ($with) {
            return $job->endpoint === $with
                && $job->queue === OnejavService::ONEJAV_QUEUE_NAME;
        });
    }

    public static function functions(): array
    {
        return [
            ['new'],
            ['popular'],
        ];
    }

    final public function testDaily(): void
    {
        Queue::fake([
            FetchItemsJob::class,
        ]);

        $this->wish->wishDaily()->wish();
        $service = app(OnejavService::class);
        $service->daily();

        Queue::assertPushed(FetchItemsJob::class, function ($job) {
            return $job->endpoint === Carbon::now()->format(CrawlingService::DEFAULT_DATE_FORMAT)
               && $job->page === 1
               && $job->queue === OnejavService::ONEJAV_QUEUE_NAME;
        });
    }
}
