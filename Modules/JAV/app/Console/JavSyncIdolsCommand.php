<?php

namespace Modules\JAV\Console;

use Illuminate\Console\Command;
use Modules\JAV\Jobs\XcityKanaSyncJob;
use Modules\JAV\Services\XcityIdolService;

class JavSyncIdolsCommand extends Command
{
    protected $signature = 'jav:sync:idols
                            {--source=xcity : Idol source provider}
                            {--concurrency=3 : Number of kana pages to process in parallel}
                            {--queue=jav : Queue name}';

    protected $description = 'Sync XCITY idol pages with per-kana cursor state and actor linking';

    public function handle(XcityIdolService $service): int
    {
        if ((string) $this->option('source') !== 'xcity') {
            $this->error('Invalid source. Supported: xcity');

            return self::INVALID;
        }

        $concurrency = max(1, (int) $this->option('concurrency'));
        $queue = (string) $this->option('queue');

        $seeds = $service->seedKanaUrls();
        if ($seeds === []) {
            $this->warn('No XCITY kana seeds found.');

            return self::SUCCESS;
        }

        $selected = $service->pickSeedsForDispatch($seeds, $concurrency);
        if ($selected->isEmpty()) {
            $this->info('No kana available to dispatch right now.');

            return self::SUCCESS;
        }

        foreach ($selected as $seed) {
            XcityKanaSyncJob::dispatch($seed['seed_key'], $seed['seed_url'])->onQueue($queue);
        }

        $this->info("Dispatched {$selected->count()} XCITY kana sync jobs to queue '{$queue}'.");

        return self::SUCCESS;
    }
}
