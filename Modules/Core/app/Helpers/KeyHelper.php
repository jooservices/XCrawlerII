<?php

namespace Modules\Core\Helpers;

class KeyHelper
{
    public static function generateKey(string $prefix, ...$args): string
    {
        return md5($prefix . serialize($args));
    }
}
