<?php

namespace Modules\Jav\Console;

use Illuminate\Console\Command;
use Modules\Jav\Services\MissAv\MissAvService;

class MissAvSync extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'jav:missav-sync {type}';

    /**
     * The console command description.
     */
    protected $description = 'Sync MissAv.';

    /**
     * Execute the console command.
     */
    final public function handle(MissAvService $service): bool
    {
        return $service->{$this->argument('type')}();
    }
}
