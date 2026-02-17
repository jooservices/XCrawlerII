<?php

namespace Modules\JAV\Support;

use Modules\JAV\Exceptions\MissavBlockedException;

class MissavHtmlGuard
{
    public static function assertNotBlocked(string $html): void
    {
        if (stripos($html, 'Just a moment') !== false || stripos($html, '__cf_chl') !== false) {
            throw new MissavBlockedException('MissAV request blocked by Cloudflare.');
        }
    }
}
