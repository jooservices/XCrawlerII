<?php

namespace Modules\StableDiffusion\Client\DTO;

use Modules\Core\Dto\BaseDto;
use stdClass;

class Text2ImageDto extends BaseDto
{
    public function __construct(protected stdClass $data)
    {
        $this->data->seed = -1;
        $this->data->samplerName = 'Euler';
        $this->data->steps = 150;
        $this->data->cfgScale = 6;
        $this->data->width = 512;
        $this->data->height = 768;
        $this->data->scheduler = null;
        $this->data->batch_size = 1;
        $this->data->n_iter = 1;
        $this->data->restore_faces = true;
        $this->data->sampler_index = 'Euler';
        $this->data->script_name = null;
        $this->data->script_args = [];
        $this->data->send_images = false;
        $this->data->save_images = true;
        $this->data->infotext = null;
        $this->data->denoising_strength = 0.35;

        $this->data->enable_hr = true;
        $this->data->hr_upscaler = 'Latent';
        $this->data->hr_scale = 1.5;
        $this->data->hr_sampler_name = 'Euler';
        $this->data->hr_second_pass_steps = 3;
    }

    public function setPrompt(PromptDto $prompt): static
    {
        $this->prompt = $prompt->toString();

        return $this;
    }

    public function setNegativePrompt(PromptDto $negativePrompt): static
    {
        $this->negativePrompt = $negativePrompt->toString();

        return $this;
    }

    public function toArray(): array
    {
        if (
            !isset($this->data->prompt)
            || !isset($this->data->negativePrompt)
        ) {
            throw new \Exception('Prompt and Negative Prompt not set');
        }

        return (array) $this->data;
    }
}
