<?php

namespace Modules\Core\Traits;

trait THasCall
{
    public function __call(string $name, mixed $arguments): mixed
    {
        if (method_exists($this, $name)) {
            return $this->{$name}(...$arguments);
        }

        return false;
    }
}
