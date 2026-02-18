<?php

namespace Modules\JAV\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class JavMigrateInteractionsCommand extends Command
{
    protected $signature = 'jav:migrate:interactions
                            {--mongo=sync : sync|skip}
                            {--search=skip : skip|sync|reset}
                            {--confirm-reset : Required when search mode is reset}
                            {--days=* : Analytics windows (7,14,30,90)}
                            {--user-id=* : Recommendation user IDs}
                            {--limit=30 : Recommendation limit per user}';

    protected $description = 'Backfill Mongo snapshots and optionally refresh Elasticsearch after interactions migration.';

    public function handle(): int
    {
        $mongoMode = (string) $this->option('mongo');
        if (! in_array($mongoMode, ['sync', 'skip'], true)) {
            $this->error('Invalid mongo mode. Supported: sync, skip');

            return self::INVALID;
        }

        $searchMode = (string) $this->option('search');
        if (! in_array($searchMode, ['skip', 'sync', 'reset'], true)) {
            $this->error('Invalid search mode. Supported: skip, sync, reset');

            return self::INVALID;
        }

        if ($searchMode === 'reset' && ! $this->option('confirm-reset')) {
            $this->error('Refusing search reset without --confirm-reset');

            return self::INVALID;
        }

        $hasFailure = false;

        if ($mongoMode === 'sync') {
            $analyticsPayload = [];
            $days = (array) $this->option('days');
            if ($days !== []) {
                $analyticsPayload['--days'] = $days;
            }

            $exitCode = Artisan::call('jav:sync:analytics', $analyticsPayload, $this->output);
            $hasFailure = $hasFailure || $exitCode !== self::SUCCESS;

            $recommendationsPayload = [
                '--limit' => (int) $this->option('limit'),
            ];
            $userIds = (array) $this->option('user-id');
            if ($userIds !== []) {
                $recommendationsPayload['--user-id'] = $userIds;
            }

            $exitCode = Artisan::call('jav:sync:recommendations', $recommendationsPayload, $this->output);
            $hasFailure = $hasFailure || $exitCode !== self::SUCCESS;
        }

        if ($searchMode !== 'skip') {
            $exitCode = Artisan::call('jav:sync:search', [
                '--mode' => $searchMode,
            ], $this->output);
            $hasFailure = $hasFailure || $exitCode !== self::SUCCESS;
        }

        return $hasFailure ? self::FAILURE : self::SUCCESS;
    }
}
