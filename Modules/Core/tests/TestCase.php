<?php

namespace Modules\Core\Tests;

use Tests\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected bool $usesRefreshDatabase = true;
}
