<?php

namespace Modules\Jav\tests;

use Modules\Core\Zeus\ZeusService;
use Modules\Jav\Models\OnejavReference;
use Modules\Jav\Zeus\Wishes\OnejavWish;
use Tests\TestCase as BaseTestCase;

class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        OnejavReference::truncate();

        app(ZeusService::class)->wish(OnejavWish::class);
    }
}
