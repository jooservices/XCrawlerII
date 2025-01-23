<?php

namespace Modules\Core\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Modules\Core\Models\Pool;
use Modules\Core\Models\Queue;

class QueueManager
{
    final public function register(
        string $serverIp,
        string $serverName,
        string $serverWanIp,
        string $name,
        ?string $description = null,
    ): Pool {
        return Pool::updateOrCreate([
            'server_ip' => $serverIp,
        ], [
            'server_name' => $serverName,
            'server_wan_ip' => $serverWanIp,
            'name' => $name,
            'description' => $description,
        ]);
    }

    final public function pushQueue(
        Pool $pool,
        string $jobClass
    ): Queue {
        return Queue::create([
            'pool_id' => $pool->id,
            'job_class' => $jobClass,
            'state_code' => Queue::STATE_CODE_INIT,
        ]);
    }

    final public function startQueue(Queue $queue): bool
    {
        return $queue->update(['state_code' => Queue::STATE_CODE_STARTED]);
    }

    final public function completeQueue(Queue $queue): bool
    {
        return $queue->update(['state_code' => Queue::STATE_CODE_COMPLETED]);
    }

    final public function failQueue(Queue $queue): bool
    {
        return $queue->update(['state_code' => Queue::STATE_CODE_FAILED]);
    }

    final public function getPoolsWithBalancing(): Collection
    {
        /**
         * Try to get pool with less queues
         */
        return Pool::with('queues')
            ->withCount('queues')
            ->when(Cache::has('last_pool_id'), function ($query) {
                $query->where('id', '<>', Cache::get('last_pool_id'));
            })
            ->orderBy('queues_count')
            ->get();
    }

    final public function getPoolWithBalancing(): Pool
    {
        $pools = $this->getPoolsWithBalancing();
        $pool = $pools->first();

        Cache::set('last_pool_id', $pool->id);

        return $this->getPoolsWithBalancing()->first();
    }
}
