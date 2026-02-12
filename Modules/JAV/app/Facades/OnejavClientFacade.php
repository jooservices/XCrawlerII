<?php

namespace Modules\JAV\Facades;

use Illuminate\Support\Facades\Facade;

class OnejavClientFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'onejav.client';
    }
}
