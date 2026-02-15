<?php

namespace Modules\JAV\Console;

use Carbon\Carbon;
use Carbon\Exceptions\InvalidFormatException;
use Illuminate\Console\Command;
use Modules\JAV\Jobs\DailySyncJob;
use Modules\JAV\Jobs\FfjavJob;
use Modules\JAV\Jobs\OneFourOneJavJob;
use Modules\JAV\Jobs\OnejavJob;
use Modules\JAV\Jobs\TagsSyncJob;

class JavSyncContentCommand extends Command
{
    protected $signature = 'jav:sync:content
                            {provider : onejav|141jav|ffjav}
                            {--type=* : new|popular|daily|tags}
                            {--date= : YYYY-MM-DD for daily type}
                            {--queue=jav : Queue name}';

    protected $description = 'Sync JAV provider content by type';

    /**
     * @var array<int, string>
     */
    private array $defaultTypes = ['new', 'daily', 'popular', 'tags'];

    public function handle(): int
    {
        $provider = (string) $this->argument('provider');
        if (! in_array($provider, ['onejav', '141jav', 'ffjav'], true)) {
            $this->error('Invalid provider. Supported: onejav, 141jav, ffjav');

            return self::INVALID;
        }

        $typesOption = (array) $this->option('type');
        $types = $typesOption === [] ? $this->defaultTypes : array_values(array_unique($typesOption));
        $exitCode = self::SUCCESS;

        foreach ($types as $syncType) {
            if (! in_array($syncType, ['new', 'popular', 'daily', 'tags'], true)) {
                $this->error('Invalid type. Supported: new, popular, daily, tags');

                $exitCode = self::INVALID;
                break;
            }

            if ($this->dispatchByType($provider, $syncType) !== self::SUCCESS) {
                $exitCode = self::INVALID;
                break;
            }
        }

        return $exitCode;
    }

    private function dispatchByType(string $provider, string $type): int
    {
        return match ($type) {
            'new' => $this->dispatchFeedJob($provider, 'new'),
            'popular' => $this->dispatchFeedJob($provider, 'popular'),
            'daily' => $this->dispatchDailyJob($provider),
            'tags' => $this->syncTags($provider),
        };
    }

    private function dispatchFeedJob(string $provider, string $type): int
    {
        $queue = (string) $this->option('queue');

        $this->info("Starting {$provider} {$type} sync.");

        match ($provider) {
            'onejav' => OnejavJob::dispatch($type)->onQueue($queue),
            '141jav' => OneFourOneJavJob::dispatch($type)->onQueue($queue),
            'ffjav' => FfjavJob::dispatch($type)->onQueue($queue),
        };

        $this->info("Dispatched {$provider} {$type} job to queue '{$queue}'.");

        return self::SUCCESS;
    }

    private function dispatchDailyJob(string $provider): int
    {
        $dateOption = $this->option('date');

        try {
            $resolvedDate = $dateOption
                ? Carbon::parse((string) $dateOption)->toDateString()
                : Carbon::now()->toDateString();
        } catch (InvalidFormatException) {
            $this->error('Invalid date. Expected format: YYYY-MM-DD');

            return self::INVALID;
        }

        $queue = (string) $this->option('queue');

        DailySyncJob::dispatch($provider, $resolvedDate, 1)->onQueue($queue);

        $this->info("Dispatched {$provider} daily sync ({$resolvedDate}) page 1.");

        return self::SUCCESS;
    }

    private function syncTags(string $provider): int
    {
        $queue = (string) $this->option('queue');
        TagsSyncJob::dispatch($provider)->onQueue($queue);

        $this->info("Dispatched {$provider} tags sync job to queue '{$queue}'.");

        return self::SUCCESS;
    }
}
