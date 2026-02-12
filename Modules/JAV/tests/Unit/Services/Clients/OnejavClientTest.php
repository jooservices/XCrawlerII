<?php

namespace Modules\JAV\Tests\Unit\Services\Clients;

use Tests\TestCase;
use Modules\JAV\Services\Clients\OnejavClient;

class OnejavClientTest extends TestCase
{
    public function test_can_get_onejav_homepage()
    {
        $client = new OnejavClient();
        $response = $client->get('/');

        $this->assertEquals(200, $response->status());
    }
}
