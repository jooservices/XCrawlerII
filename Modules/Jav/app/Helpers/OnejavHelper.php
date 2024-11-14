<?php

namespace Modules\Jav\Helpers;

use Carbon\Carbon;
use Modules\Jav\Onejav\CrawlingService;

class OnejavHelper
{
    public static function parseDvdId(string $dvdId): string
    {
        return implode(
            '-',
            preg_split('/(,?\\s+)|((?<=[a-z])(?=\\d))|((?<=\\d)(?=[a-z]))/i', $dvdId)
        );
    }

    public static function convertSize(string $size): float
    {
        if (str_contains($size, 'MB')) {
            $size = (float) trim(str_replace('MB', '', $size));
            $size /= 1024;
        } elseif (str_contains($size, 'GB')) {
            $size = (float) trim(str_replace('GB', '', $size));
        }

        return $size;
    }

    public static function convertToDate(string $date): ?Carbon
    {
        $dateTime = Carbon::createFromFormat(CrawlingService::DEFAULT_DATE_FORMAT, trim($date, '/'));

        if (!$dateTime) {
            return null;
        }

        return $dateTime;
    }
}
