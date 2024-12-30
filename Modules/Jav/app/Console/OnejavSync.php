<?php

namespace Modules\Jav\Console;

use Illuminate\Console\Command;
use Modules\Jav\Services\Onejav\OnejavService;

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
    final public function handle(): void
    {
        $service = app(OnejavService::class);

        if (!method_exists($service, $this->argument('type'))) {
            return;
        }

        $service->{$this->argument('type')}();
    }
}
