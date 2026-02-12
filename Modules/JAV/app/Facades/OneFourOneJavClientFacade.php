<?php

namespace Modules\JAV\Facades;

use Illuminate\Support\Facades\Facade;

class OneFourOneJavClientFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'onefouronejav.client';
    }
}
