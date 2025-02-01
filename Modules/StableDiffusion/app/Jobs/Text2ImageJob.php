<?php

namespace Modules\StableDiffusion\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\StableDiffusion\Client\DTO\Text2ImageDto;
use Modules\StableDiffusion\Client\StableDiffusionSdk;

class Text2ImageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        private readonly Text2ImageDto $dto
    ) {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(StableDiffusionSdk $sdk): void
    {
        $sdk->txt2img($this->dto);
    }
}
