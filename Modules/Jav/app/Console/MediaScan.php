<?php

namespace Modules\Jav\Console;

use Illuminate\Console\Command;
use Modules\Core\Services\MediaService;

class MediaScan extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'jav:media-scan {--dir=}';

    /**
     * The console command description.
     */
    protected $description = 'Scan media files.';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $service = app(MediaService::class);
        $service->mediaScan(
            gethostname(),
            gethostbyname(gethostname()),
            $this->option('dir')
        );
    }
}
