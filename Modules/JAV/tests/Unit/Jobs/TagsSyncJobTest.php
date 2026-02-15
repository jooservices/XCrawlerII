<?php

namespace Modules\JAV\Tests\Unit\Jobs;

use Mockery;
use Modules\JAV\Jobs\TagsSyncJob;
use Modules\JAV\Services\OnejavService;
use Modules\JAV\Tests\TestCase;

class TagsSyncJobTest extends TestCase
{
    public function test_unique_id_is_based_on_source(): void
    {
        $job = new TagsSyncJob('onejav');

        $this->assertEquals('onejav', $job->uniqueId());
    }

    public function test_handle_calls_tags_on_resolved_service(): void
    {
        $service = Mockery::mock(OnejavService::class);
        $service->shouldReceive('tags')
            ->once()
            ->andReturn(collect(['4K']));
        $this->app->instance(OnejavService::class, $service);

        $job = new TagsSyncJob('onejav');
        $job->handle();
    }
}
