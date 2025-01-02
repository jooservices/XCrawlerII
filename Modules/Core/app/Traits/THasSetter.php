<?php

namespace Modules\Core\Traits;

use Illuminate\Support\Str;

trait THasSetter
{
    public function hasSetter(string $name, mixed $value): bool|string
    {
        $methodName = 'set' . Str::studly($name);

        return
            method_exists($this, $methodName)($value)
            ? $methodName
                : false;
    }
}
