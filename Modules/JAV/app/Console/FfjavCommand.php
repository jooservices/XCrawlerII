<?php

namespace Modules\JAV\Console;

use Illuminate\Console\Command;

class FfjavCommand extends Command
{
    protected $signature = 'jav:ffjav {type : new|popular}';

    protected $description = 'Sync ffjav content (new or popular)';

    public function handle(): void
    {
        $type = $this->argument('type');

        if (! in_array($type, ['new', 'popular'])) {
            $this->error('Invalid type. Supported types: new, popular');

            return;
        }

        $this->info("Starting sync for: $type");

        \Modules\JAV\Jobs\FfjavJob::dispatch($type)->onQueue('jav');

        $this->info("Job dispatched to queue 'jav'.");
    }
}
