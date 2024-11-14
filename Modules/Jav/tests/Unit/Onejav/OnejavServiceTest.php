<?php

namespace Modules\Jav\Tests\Unit\Onejav;

use Illuminate\Support\Facades\Queue;
use Modules\Jav\Jobs\OnejavFetchItemsJob;
use Modules\Jav\Services\OnejavService;
use Modules\Jav\tests\TestCase;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class OnejavServiceTest extends TestCase
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function testNew(): void
    {
        Queue::fake([
            OnejavFetchItemsJob::class,
        ]);
        $service = app(OnejavService::class);
        $service->new();

        Queue::assertPushed(OnejavFetchItemsJob::class, function ($job) {
            return $job->queue === OnejavService::ONEJAV_QUEUE_NAME;
        });
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    //    public function testPopular(): void
    //    {
    //        $service = app(OnejavService::class);
    //        $service->popular();
    //
    //        $this->assertDatabaseHas(
    //            'settings',
    //            [
    //                'group' => 'onejav',
    //                'key' => 'popular_last_page',
    //                'value' => 4,
    //            ],
    //            'mongodb'
    //        );
    //        $this->assertDatabaseHas(
    //            'settings',
    //            [
    //                'group' => 'onejav',
    //                'key' => 'popular_current_page',
    //                'value' => 2,
    //            ],
    //            'mongodb'
    //        );
    //
    //        $service->popular();
    //        $this->assertDatabaseHas(
    //            'settings',
    //            [
    //                'group' => 'onejav',
    //                'key' => 'popular_current_page',
    //                'value' => 3,
    //            ],
    //            'mongodb'
    //        );
    //    }
}
