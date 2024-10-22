<?php

namespace Modules\Client\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase as BaseTestCase;

class TestCase extends BaseTestCase
{
    use WithFaker;
    use RefreshDatabase;
}
