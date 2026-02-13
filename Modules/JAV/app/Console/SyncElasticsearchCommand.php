<?php

namespace Modules\JAV\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Modules\JAV\Models\Actor;
use Modules\JAV\Models\Jav;
use Modules\JAV\Models\Tag;

class SyncElasticsearchCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'jav:sync-es';

    /**
     * The console command description.
     */
    protected $description = 'Sync all JAV data (Movies, Actors, Tags) to Elasticsearch';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $this->info('Starting Elasticsearch synchronization...');

        $this->syncModel(Jav::class, 'JAV Movies');
        $this->syncModel(Actor::class, 'Actors');
        $this->syncModel(Tag::class, 'Tags');

        $this->info('All data synced successfully!');
    }

    protected function syncModel(string $model, string $label): void
    {
        $this->info("Syncing {$label}...");

        $exitCode = Artisan::call('scout:import', [
            'searchable' => $model,
        ], $this->output);

        if ($exitCode === 0) {
            $this->info("{$label} synced.");
        } else {
            $this->error("Failed to sync {$label}.");
        }
    }
}
