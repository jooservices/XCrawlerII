<?php

namespace Modules\Jav\tests;

use Modules\Jav\Models\OnejavReference;
use Modules\Jav\Zeus\Wishes\OnejavWish;
use Tests\TestCase as BaseTestCase;

class TestCase extends BaseTestCase
{
    protected OnejavWish $wish;

    public function setUp(): void
    {
        parent::setUp();

        OnejavReference::truncate();

        $this->wish = app(OnejavWish::class);
    }
}
