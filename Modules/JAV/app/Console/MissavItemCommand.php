<?php

namespace Modules\JAV\Console;

use Illuminate\Console\Command;
use Modules\JAV\Models\MissavSchedule;
use Modules\JAV\Services\MissavService;

class MissavItemCommand extends Command
{
    protected $signature = 'jav:missav:item {url? : MissAV item URL (optional)}';

    protected $description = 'Fetch a single MissAV item and persist it (or pick from schedule if missing).';

    public function handle(MissavService $service): int
    {
        $url = (string) ($this->argument('url') ?? '');

        if ($url !== '') {
            $this->processUrl($service, $url);
            $this->info('MissAV item processed.');

            return self::SUCCESS;
        }

        $schedule = MissavSchedule::where('status', 'pending')
            ->orderBy('id')
            ->first();

        if ($schedule === null) {
            $this->info('No pending MissAV schedule items to process.');

            return self::SUCCESS;
        }

        $this->processSchedule($service, $schedule);
        $this->info('MissAV scheduled item processed.');

        return self::SUCCESS;
    }

    private function processUrl(MissavService $service, string $url): void
    {
        $schedule = MissavSchedule::where('url', $url)->first();
        if ($schedule === null) {
            $service->item($url);

            return;
        }

        $this->processSchedule($service, $schedule);
    }

    private function processSchedule(MissavService $service, MissavSchedule $schedule): void
    {
        $schedule->update([
            'status' => 'processing',
            'attempts' => $schedule->attempts + 1,
        ]);

        try {
            $service->item($schedule->url);

            $schedule->update([
                'status' => 'done',
                'processed_at' => now(),
                'last_error' => null,
            ]);
        } catch (\Throwable $exception) {
            $schedule->update([
                'status' => 'failed',
                'last_error' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }
}
