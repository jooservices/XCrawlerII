<?php

namespace Modules\JAV\Tests\Unit\Services;

use Modules\JAV\Exceptions\MissavBlockedException;
use Modules\JAV\Support\MissavHtmlGuard;
use Modules\JAV\Tests\TestCase;

class MissavHtmlGuardTest extends TestCase
{
    public function test_blocks_cloudflare_html(): void
    {
        $html = $this->loadFixture('missav/missav_cloudflare.html');

        $this->expectException(MissavBlockedException::class);
        MissavHtmlGuard::assertNotBlocked($html);
    }
}
