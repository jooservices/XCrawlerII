<?php

namespace Modules\JAV\Tests\Feature\Commands;

use Elastic\Elasticsearch\Client;
use Modules\JAV\Tests\TestCase;

class JavSyncSearchCommandTest extends TestCase
{
    public function test_command_returns_success_when_elasticsearch_client_is_not_usable(): void
    {
        $client = (new \ReflectionClass(Client::class))->newInstanceWithoutConstructor();
        $this->app->instance(Client::class, $client);

        $this->artisan('jav:sync:search')->assertExitCode(0);
    }
}
