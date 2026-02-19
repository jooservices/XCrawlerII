<?php

namespace Modules\Core\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Modules\Core\Observability\Contracts\ObservabilityClientInterface;
use Modules\Core\Observability\Contracts\TelemetryEmitterInterface;
use Modules\Core\Observability\ObsHttpClient;
use Modules\Core\Observability\TelemetryEmitter;
use Nwidart\Modules\Traits\PathNamespace;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class CoreServiceProvider extends ServiceProvider
{
    use PathNamespace;

    protected string $name = 'Core';

    protected string $nameLower = 'core';

    /**
     * Boot the application events.
     */
    public function boot(): void
    {
        $this->registerCommands();
        $this->registerCommandSchedules();
        $this->registerAnalyticsRateLimiter();
        $this->registerTranslations();
        $this->registerConfig();
        $this->registerViews();
        $this->loadMigrationsFrom(module_path($this->name, 'database/migrations'));
    }

    /**
     * Register the service provider.
     */
    public function register(): void
    {
        $this->app->register(EventServiceProvider::class);
        $this->app->register(RouteServiceProvider::class);
        $this->app->register(\Matchish\ScoutElasticSearch\ElasticSearchServiceProvider::class);

        $this->app->bind(ObservabilityClientInterface::class, ObsHttpClient::class);
        $this->app->singleton(TelemetryEmitterInterface::class, TelemetryEmitter::class);

        $this->app->singleton(\Modules\Core\Services\ConfigService::class, function () {
            return new \Modules\Core\Services\ConfigService;
        });

        $this->app->alias(\Modules\Core\Services\ConfigService::class, 'core.config');
    }

    /**
     * Register commands in the format of Command::class
     */
    protected function registerCommands(): void
    {
        $this->commands([
            \Modules\Core\Console\AuthAuthorizeCommand::class,
            \Modules\Core\Console\ObsDependenciesHealthCommand::class,
            \Modules\Core\Console\FlushAnalyticsCommand::class,
            \Modules\Core\Console\AnalyticsParityCheckCommand::class,
            \Modules\Core\Console\AnalyticsReportGenerateCommand::class,
            \Modules\Core\Console\AnalyticsReportVerifyCommand::class,
            \Modules\Core\Console\Tests\AnalyticsBenchmarkCommand::class,
        ]);
    }

    /**
     * Register command Schedules.
     */
    protected function registerCommandSchedules(): void
    {
        $this->app->booted(function (): void {
            /** @var Schedule $schedule */
            $schedule = $this->app->make(Schedule::class);

            if ((bool) config('services.obs.dependency_health.schedule_enabled', false)) {
                $schedule->command('obs:dependencies-health')
                    ->cron((string) config('services.obs.dependency_health.schedule_cron', '*/5 * * * *'))
                    ->withoutOverlapping()
                    ->onOneServer();
            }

            if ((bool) config('analytics.schedule_flush', true)) {
                $flushIntervalMinutes = max(1, min(59, (int) config('analytics.flush_interval_minutes', 1)));
                $schedule->command('analytics:flush')
                    ->cron(sprintf('*/%d * * * *', $flushIntervalMinutes))
                    ->withoutOverlapping()
                    ->onOneServer();
            }

            if ((bool) config('analytics.evidence.schedule_daily', true)) {
                $dailyAt = (string) config('analytics.evidence.daily_at', '00:10');
                $days = max(1, (int) config('analytics.evidence.days', 7));
                $limit = max(1, (int) config('analytics.evidence.limit', 500));
                $outputDir = (string) config('analytics.evidence.output_dir', 'logs/analytics/evidence');
                $schedule->command('analytics:report:generate', [
                    '--days' => $days,
                    '--limit' => $limit,
                    '--dir' => storage_path($outputDir),
                    '--archive' => (bool) config('analytics.evidence.archive', true),
                    '--rollback' => (bool) config('analytics.evidence.rollback', true),
                ])
                    ->dailyAt($dailyAt)
                    ->withoutOverlapping()
                    ->onOneServer();
            }
        });
    }

    private function registerAnalyticsRateLimiter(): void
    {
        $this->app->booted(function (): void {
            RateLimiter::for('analytics', function ($request) {
                return Limit::perMinute((int) config('analytics.rate_limit_per_minute', 60))
                    ->by($request->ip());
            });
        });
    }

    /**
     * Register translations.
     */
    public function registerTranslations(): void
    {
        $langPath = resource_path('lang/modules/'.$this->nameLower);

        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, $this->nameLower);
            $this->loadJsonTranslationsFrom($langPath);
        } else {
            $this->loadTranslationsFrom(module_path($this->name, 'lang'), $this->nameLower);
            $this->loadJsonTranslationsFrom(module_path($this->name, 'lang'));
        }
    }

    /**
     * Register config.
     */
    protected function registerConfig(): void
    {
        $configPath = module_path($this->name, config('modules.paths.generator.config.path'));

        if (is_dir($configPath)) {
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($configPath));

            foreach ($iterator as $file) {
                if ($file->isFile() && $file->getExtension() === 'php') {
                    $config = str_replace($configPath.DIRECTORY_SEPARATOR, '', $file->getPathname());
                    $config_key = str_replace([DIRECTORY_SEPARATOR, '.php'], ['.', ''], $config);
                    $segments = explode('.', $this->nameLower.'.'.$config_key);

                    // Remove duplicated adjacent segments
                    $normalized = [];
                    foreach ($segments as $segment) {
                        if (end($normalized) !== $segment) {
                            $normalized[] = $segment;
                        }
                    }

                    $key = ($config === 'config.php') ? $this->nameLower : implode('.', $normalized);

                    $this->publishes([$file->getPathname() => config_path($config)], 'config');
                    $this->merge_config_from($file->getPathname(), $key);
                }
            }
        }
    }

    /**
     * Merge config from the given path recursively.
     */
    protected function merge_config_from(string $path, string $key): void
    {
        $existing = config($key, []);
        $module_config = require $path;

        config([$key => array_replace_recursive($existing, $module_config)]);
    }

    /**
     * Register views.
     */
    public function registerViews(): void
    {
        $viewPath = resource_path('views/modules/'.$this->nameLower);
        $sourcePath = module_path($this->name, 'resources/views');

        $this->publishes([$sourcePath => $viewPath], ['views', $this->nameLower.'-module-views']);

        $this->loadViewsFrom(array_merge($this->getPublishableViewPaths(), [$sourcePath]), $this->nameLower);

        Blade::componentNamespace(config('modules.namespace').'\\'.$this->name.'\\View\\Components', $this->nameLower);
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return [];
    }

    private function getPublishableViewPaths(): array
    {
        $paths = [];
        foreach (config('view.paths') as $path) {
            if (is_dir($path.'/modules/'.$this->nameLower)) {
                $paths[] = $path.'/modules/'.$this->nameLower;
            }
        }

        return $paths;
    }
}
