<?php

namespace Modules\JAV\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Modules\JAV\Models\MissavSchedule;
use Modules\JAV\Services\MissavService;

class MissavProcessScheduleCommand extends Command
{
    protected $signature = 'jav:missav:process {--limit= : Max pending items to process}';

    protected $description = 'Process MissAV detail items from the schedule table.';

    public function handle(): int
    {
        $limit = (int) ($this->option('limit') ?: config('jav.missav.schedule_batch', 5));
        if ($limit <= 0) {
            $limit = 5;
        }

        $picked = collect();

        DB::transaction(function () use ($limit, &$picked): void {
            $picked = MissavSchedule::where('status', 'pending')
                ->orderBy('id')
                ->limit($limit)
                ->lockForUpdate()
                ->get();

            foreach ($picked as $schedule) {
                $schedule->update([
                    'status' => 'queued',
                    'scheduled_at' => now(),
                ]);
            }
        });

        if ($picked->isEmpty()) {
            $this->info('No pending MissAV schedule items to dispatch.');
            return self::SUCCESS;
        }

        foreach ($picked as $schedule) {
            $this->processSchedule($schedule, $this->laravel->make(MissavService::class));
        }

        $this->info("Processed {$picked->count()} MissAV schedule items.");

        return self::SUCCESS;
    }

    private function processSchedule(MissavSchedule $schedule, MissavService $service): void
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
