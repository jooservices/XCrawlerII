<?php

namespace Modules\StableDiffusion\Tests\Unit\Client;

use Modules\StableDiffusion\Client\DTO\PromptDto;
use Modules\StableDiffusion\Client\DTO\Text2ImageDto;
use Modules\StableDiffusion\Client\StableDiffusionSdk;
use Tests\TestCase;

class StableDiffusionSdkTest extends TestCase
{
    public function testText2Image()
    {
        $text2Image = app(Text2ImageDto::class);
        $text2Image->setPrompt(
            app(PromptDto::class)->addPrompts('Light pink hair,pink eyes,pink and white,sakura leafs,vivid colors,white dress,paint splash,simple background,ray tracing,wavy hair')
        );
        $text2Image->setNegativePrompt(
            app(PromptDto::class)->addPrompts('(worst quality:2),(low quality:2),(normal quality:2),lowres,bad anatomy,watermark,(worst quality:2),(low quality:2),(normal quality:2),lowres,badhandv4,(deformed iris, deformed pupils, semi-realistic, cgi, 3d, render, sketch, cartoon, drawing, anime, mutated hands and fingers:1.4),(deformed, distorted, disfigured:1.3),poorly drawn,bad anatomy,wrong anatomy,extra limb,missing limb,floating limbs,disconnected limbs,mutation,mutated,ugly,disgusting,amputation')
        );

        // app(StableDiffusionSdk::class)->txt2img($text2Image);
    }
}
