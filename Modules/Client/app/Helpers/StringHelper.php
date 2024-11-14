<?php

namespace Modules\Client\Helpers;

class StringHelper
{
    public static function convertToUTF8a(array $array): array
    {
        array_walk_recursive($array, function (&$item) {
            if (!mb_detect_encoding($item, 'utf-8', true)) {
                $item = mb_convert_encoding($item, 'UTF-8', 'ISO-8859-1');
            }
        });

        return $array;
    }
}
