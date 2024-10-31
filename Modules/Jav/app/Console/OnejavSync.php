<?php

namespace Modules\Jav\Console;

use Illuminate\Console\Command;
use Modules\Jav\Services\OnejavService;

class OnejavSync extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'jav:onejav-sync {type}';

    /**
     * The console command description.
     */
    protected $description = 'Sync Onejav.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $service = app(OnejavService::class);
        switch ($this->argument('type')) {
            case 'daily':
                $service->daily();
                break;
            case 'new':
                $service->new();
                break;
            case 'popular':
                $service->popular();
                break;
        }
    }
}
