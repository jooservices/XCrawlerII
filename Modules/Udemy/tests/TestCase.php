<?php

namespace Modules\Udemy\Tests;

use Modules\Udemy\Client\UdemySdk;
use Modules\Udemy\Models\UserToken;
use Modules\Udemy\Zeus\Wishes\UdemyWish;
use Tests\TestCase as BaseTestCase;

class TestCase extends BaseTestCase
{
    protected UdemySdk $udemySdk;

    protected UserToken $userToken;

    protected UdemyWish $wish;

    protected bool $useWish = true;

    public function setUp(): void
    {
        parent::setUp();

        $this->userToken = UserToken::factory()->create();
        $this->wish = app(UdemyWish::class);
    }
}
