<?php

namespace Modules\JAV\Tests\Unit\Services\Clients;

use Tests\TestCase;
use Modules\JAV\Services\Clients\OneFourOneJavClient;

class OneFourOneJavClientTest extends TestCase
{
    public function test_can_get_141jav_homepage()
    {
        $client = new OneFourOneJavClient();
        $response = $client->get('/');

        $this->assertEquals(200, $response->status());
    }
}
