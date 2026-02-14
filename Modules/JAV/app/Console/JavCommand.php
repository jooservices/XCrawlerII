<?php

namespace Modules\JAV\Console;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Modules\JAV\Jobs\DailySyncJob;
use Modules\JAV\Jobs\FfjavJob;
use Modules\JAV\Jobs\OneFourOneJavJob;
use Modules\JAV\Jobs\OnejavJob;
use Modules\JAV\Services\FfjavService;
use Modules\JAV\Services\OneFourOneJavService;
use Modules\JAV\Services\OnejavService;

class JavCommand extends Command
{
    protected $signature = 'jav:sync
                            {provider : onejav|141jav|ffjav}
                            {--type= : new|popular|daily|tags}
                            {--date= : YYYY-MM-DD for daily type}';

    protected $description = 'Sync JAV provider content by type; defaults to daily, popular, and tags';

    /**
     * @var array<int, string>
     */
    private array $defaultTypes = ['new', 'daily', 'popular', 'tags'];

    public function handle(): int
    {
        $provider = $this->argument('provider');

        if (!in_array($provider, ['onejav', '141jav', 'ffjav'], true)) {
            $this->error('Invalid provider. Supported: onejav, 141jav, ffjav');

            return self::INVALID;
        }

        $type = $this->option('type');
        $types = $type === null ? $this->defaultTypes : [$type];

        foreach ($types as $syncType) {
            if (!in_array($syncType, ['new', 'popular', 'daily', 'tags'], true)) {
                $this->error('Invalid type. Supported: new, popular, daily, tags');

                return self::INVALID;
            }

            $this->dispatchByType($provider, $syncType);
        }

        return self::SUCCESS;
    }

    private function dispatchByType(string $provider, string $type): void
    {
        match ($type) {
            'new' => $this->dispatchFeedJob($provider, 'new'),
            'popular' => $this->dispatchFeedJob($provider, 'popular'),
            'daily' => $this->dispatchDailyJob($provider),
            'tags' => $this->syncTags($provider),
        };
    }

    private function dispatchFeedJob(string $provider, string $type): void
    {
        $this->info("Starting {$provider} {$type} sync.");

        match ($provider) {
            'onejav' => OnejavJob::dispatch($type)->onQueue('jav'),
            '141jav' => OneFourOneJavJob::dispatch($type)->onQueue('jav'),
            'ffjav' => FfjavJob::dispatch($type)->onQueue('jav'),
        };

        $this->info("Dispatched {$provider} {$type} job to queue 'jav'.");
    }

    private function dispatchDailyJob(string $provider): void
    {
        $dateOption = $this->option('date');
        $resolvedDate = $dateOption
            ? Carbon::parse($dateOption)->toDateString()
            : Carbon::now()->toDateString();

        DailySyncJob::dispatch($provider, $resolvedDate, 1)->onQueue('jav');

        $this->info("Dispatched {$provider} daily sync ({$resolvedDate}) page 1.");
    }

    private function syncTags(string $provider): void
    {
        $service = match ($provider) {
            'onejav' => app(OnejavService::class),
            '141jav' => app(OneFourOneJavService::class),
            'ffjav' => app(FfjavService::class),
        };

        $tags = $service->tags();

        $this->info("Synced {$tags->count()} tags for {$provider}.");
    }
}
