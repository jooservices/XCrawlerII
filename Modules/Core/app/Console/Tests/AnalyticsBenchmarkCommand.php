<?php

namespace Modules\Core\Console\Tests;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;
use Modules\Core\Enums\AnalyticsAction;
use Modules\Core\Enums\AnalyticsDomain;
use Modules\Core\Enums\AnalyticsEntityType;
use Modules\Core\Services\AnalyticsFlushService;

class AnalyticsBenchmarkCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:analytics-benchmark {--count=10000}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Benchmark analytics flush performance (Testing only)';

    /**
     * Execute the console command.
     */
    public function handle(AnalyticsFlushService $service): void
    {
        $count = (int) $this->option('count');
        $this->info("Benchmarking with {$count} keys...");

        // 1. Seed
        $this->info('Seeding Redis...');
        $prefix = config('analytics.redis_prefix', 'anl:counters');
        $domain = AnalyticsDomain::Jav->value;
        $entityType = AnalyticsEntityType::Movie->value;

        Redis::pipeline(function ($pipe) use ($count, $prefix, $domain, $entityType) {
            for ($i = 0; $i < $count; $i++) {
                $uuid = Str::uuid();
                $key = "{$prefix}:{$domain}:{$entityType}:{$uuid}";
                $pipe->hincrby($key, AnalyticsAction::View->value, 1);
            }
        });
        $this->newLine();
        $this->info('Seeding complete.');

        // 2. Measure
        $this->info('Flushing...');
        $start = microtime(true);

        $result = $service->flush();

        $end = microtime(true);
        $duration = $end - $start;

        $this->info('Flush complete in '.number_format($duration, 4).' seconds.');
        $this->info("Keys processed: {$result['keys_processed']}");
        $this->info("Errors: {$result['errors']}");
        if ($duration > 0) {
            $this->info('Throughput: '.number_format($result['keys_processed'] / $duration, 2).' keys/sec');
        }
    }
}
