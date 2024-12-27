<?php

namespace Modules\Udemy\Tests;

use Modules\Core\Zeus\ZeusService;
use Modules\Udemy\Client\UdemySdk;
use Modules\Udemy\Models\UserToken;
use Modules\Udemy\Zeus\Wishes\UdemyWish;
use Tests\TestCase as BaseTestCase;

class TestCase extends BaseTestCase
{
    protected UdemySdk $udemySdk;

    protected bool $useWish = true;

    public function setUp(): void
    {
        parent::setUp();

        if ($this->useWish) {
            app(ZeusService::class)->wish(UdemyWish::class);
            $this->udemySdk = app(UdemySdk::class)->setToken(UserToken::factory()->create());
        }
    }
}
