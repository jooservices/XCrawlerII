<?php

namespace Modules\StableDiffusion\Tests\Unit\Client;

use Modules\StableDiffusion\Client\StableDiffusionSdk;
use Tests\TestCase;

class StableDiffusionSdkTest extends TestCase
{
    public function testText2Image()
    {
        app(StableDiffusionSdk::class)->txt2img("test");
    }
}
