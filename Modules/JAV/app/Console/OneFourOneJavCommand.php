<?php

namespace Modules\JAV\Console;

use Illuminate\Console\Command;

class OneFourOneJavCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'jav:141 {type : new|popular}';

    /**
     * The console command description.
     */
    protected $description = 'Sync 141jav content (new or popular)';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $type = $this->argument('type');

        if (!in_array($type, ['new', 'popular'])) {
            $this->error('Invalid type. Supported types: new, popular');
            return;
        }

        $this->info("Starting sync for: $type");

        \Modules\JAV\Jobs\OneFourOneJavJob::dispatch($type)->onQueue('jav');

        $this->info("Job dispatched to queue 'jav'.");
    }
}
