<?php

namespace Modules\Core\Zeus;

use Illuminate\Foundation\Testing\WithFaker;
use Modules\Core\Zeus\Interfaces\IWish;

abstract class AbstractWish implements IWish
{
    use WithFaker;

    public function __construct()
    {
        $this->setUpFaker();
    }
}
