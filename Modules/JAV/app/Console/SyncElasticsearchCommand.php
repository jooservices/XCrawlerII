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
    protected $signature = 'jav:elastichsearch
                            {--type=sync : sync|reset}';

    /**
     * The console command description.
     */
    protected $description = 'Sync or reset JAV Elasticsearch indexes (Movies, Actors, Tags)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $type = (string) $this->option('type');
        if (!in_array($type, ['sync', 'reset'], true)) {
            $this->error('Invalid type. Supported: sync, reset');

            return self::INVALID;
        }

        if ($type === 'reset') {
            $this->info('Starting Elasticsearch reset...');

            $this->flushModel(Jav::class, 'JAV Movies');
            $this->flushModel(Actor::class, 'Actors');
            $this->flushModel(Tag::class, 'Tags');

            $this->info('Elasticsearch indexes reset successfully.');

            return self::SUCCESS;
        }

        $this->info('Starting Elasticsearch synchronization...');

        $results = [
            $this->syncModel(Jav::class, 'JAV Movies'),
            $this->syncModel(Actor::class, 'Actors'),
            $this->syncModel(Tag::class, 'Tags'),
        ];

        $this->newLine();
        $this->info('Elasticsearch sync report:');
        $this->table(
            ['Model', 'Synced Count', 'Status'],
            array_map(static function (array $result): array {
                return [$result['model'], (string) $result['count'], $result['status']];
            }, $results)
        );

        $totalSynced = array_sum(array_column($results, 'count'));
        $this->info("Total synced records: {$totalSynced}");

        $hasFailed = in_array('failed', array_column($results, 'status'), true);
        if ($hasFailed) {
            $this->error('Elasticsearch synchronization finished with failures.');

            return self::FAILURE;
        }

        $this->info('All data synced successfully!');

        return self::SUCCESS;
    }

    protected function syncModel(string $model, string $label): array
    {
        $this->info("Syncing {$label}...");

        $count = $model::query()->count();

        $exitCode = Artisan::call('scout:import', [
            'searchable' => $model,
        ], $this->output);

        if ($exitCode === 0) {
            $this->info("{$label} synced.");

            return [
                'model' => $label,
                'count' => $count,
                'status' => 'synced',
            ];
        }

        $this->error("Failed to sync {$label}.");

        return [
            'model' => $label,
            'count' => $count,
            'status' => 'failed',
        ];
    }

    protected function flushModel(string $model, string $label): void
    {
        $this->info("Resetting {$label}...");

        $exitCode = Artisan::call('scout:flush', [
            'searchable' => $model,
        ], $this->output);

        if ($exitCode === 0) {
            $this->info("{$label} reset.");
        } else {
            $this->error("Failed to reset {$label}.");
        }
    }
}
