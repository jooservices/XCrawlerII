<?php

namespace Modules\StableDiffusion\Client\DTO;

use Illuminate\Support\Collection;
use Modules\Jav\Dto\BaseDto;

/**
 * @property array $prompts
 */
class PromptDto extends BaseDto
{
    public function getPrompts(): Collection
    {
        return collect($this->prompts);
    }

    public function setPrompts(array $prompts): self
    {
        $this->prompts = $prompts;

        return $this;
    }

    public function addPrompt(string $prompt): self
    {
        $this->prompts[] = $prompt;

        return $this;
    }

    public function addPrompts(string $prompts): self
    {
        $this->prompts = array_merge($this->prompts ?? [], explode(',', $prompts));

        return $this;
    }

    public function toString(): string
    {
        return $this->getPrompts()->unique()->implode(', ');
    }
}
