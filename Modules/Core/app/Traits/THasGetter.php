<?php

namespace Modules\Core\Traits;

use Illuminate\Support\Str;

trait THasGetter
{
    public function hasGetter(string $name): bool|string
    {
        $methodName = 'get' . Str::studly($name);

        return
            method_exists($this, $methodName)
            ? $methodName
                : false;
    }
}
