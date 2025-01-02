<?php

namespace Modules\Jav\tests;

use Modules\Core\Zeus\Wishes\FactoryWish;
use Modules\Jav\Models\OnejavReference;
use Modules\Jav\Zeus\Wishes\OnejavWish;
use Tests\TestCase as BaseTestCase;

class TestCase extends BaseTestCase
{
    protected FactoryWish $wish;

    public function setUp(): void
    {
        parent::setUp();

        OnejavReference::truncate();

        $this->wish = app(OnejavWish::class);
    }
}
