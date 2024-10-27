<?php

namespace Modules\Core\Tests\Unit\Services;

use Illuminate\Support\Facades\Cache;
use Modules\Core\Models\Pool;
use Modules\Core\Models\Queue;
use Modules\Core\Services\QueueManager;
use Modules\Core\Tests\TestCase;

class QueueManagerTest extends TestCase
{
    public function testRegisterQueue()
    {
        $manager = app(QueueManager::class);
        $pool = $manager->register(
            $this->faker->localIpv4,
            $this->faker->word,
            $this->faker->ipv4,
            $this->faker->word,
        );

        $this->assertInstanceOf(Pool::class, $pool);
    }

    public function testPushJob()
    {
        $pool = Pool::factory()->create();
        $manager = app(QueueManager::class);

        $job = $manager->pushQueue(
            $pool,
            $this->faker->word,
        );
        $this->assertInstanceOf(Queue::class, $job);
        $this->assertTrue($pool->queues->first()->is($job));
    }

    public function testGetPoolWithBalacing()
    {
        Pool::factory()
            ->count(10)
            ->create();

        $manager = app(QueueManager::class);
        $pools = $manager->getPoolsWithBalancing();
        $firstPool = $pools->first();

        $this->assertLessThanOrEqual($pools->last()->queues_count, $firstPool->queues_count);

        // Exclude this pool
        Cache::set('last_pool_id', $firstPool->id);
        $pools = $manager->getPoolsWithBalancing();
        $this->assertFalse($pools->first()->is($firstPool));
    }
}
