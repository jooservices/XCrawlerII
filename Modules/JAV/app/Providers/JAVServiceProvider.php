<?php

namespace Modules\JAV\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Nwidart\Modules\Traits\PathNamespace;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class JAVServiceProvider extends ServiceProvider
{
    use PathNamespace;

    protected string $name = 'JAV';

    protected string $nameLower = 'jav';

    /**
     * Boot the application events.
     */
    public function boot(): void
    {
        $this->registerCommands();
        $this->registerCommandSchedules();
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

        $this->app->bind(\Modules\JAV\Services\Clients\OnejavClient::class, function () {
            return new \Modules\JAV\Services\Clients\OnejavClient(
                \JOOservices\Client\Client\ClientBuilder::create()
                    ->withBaseUri('https://onejav.com')
                    ->withDefaultLogging('onejav-client')
                    ->build()
            );
        });

        $this->app->bind(\Modules\JAV\Services\Clients\FfjavClient::class, function () {
            return new \Modules\JAV\Services\Clients\FfjavClient(
                \JOOservices\Client\Client\ClientBuilder::create()
                    ->withBaseUri('https://ffjav.com')
                    ->withDefaultLogging('ffjav-client')
                    ->build()
            );
        });

        $this->app->bind(\Modules\JAV\Services\Clients\XcityClient::class, function () {
            return new \Modules\JAV\Services\Clients\XcityClient(
                \JOOservices\Client\Client\ClientBuilder::create()
                    ->withBaseUri('https://xxx.xcity.jp')
                    ->withDefaultLogging('xcity-client')
                    ->build()
            );
        });
    }

    /**
     * Register commands in the format of Command::class
     */
    protected function registerCommands(): void
    {
        $this->app->bind(\Modules\JAV\Services\Clients\OneFourOneJavClient::class, function () {
            return new \Modules\JAV\Services\Clients\OneFourOneJavClient(
                \JOOservices\Client\Client\ClientBuilder::create()
                    ->withBaseUri('https://www.141jav.com')
                    ->withDefaultLogging('onefouronejav-client')
                    ->build()
            );
        });

        $this->commands([
            \Modules\JAV\Console\JavCommand::class,
            \Modules\JAV\Console\SyncElasticsearchCommand::class,
            \Modules\JAV\Console\XcityIdolSyncCommand::class,
        ]);
    }

    /**
     * Register command Schedules.
     */
    protected function registerCommandSchedules(): void
    {
        $this->app->booted(function () {
            $schedule = $this->app->make(Schedule::class);
            $schedule->command('jav:sync onejav --type=new')->everyMinute()->runInBackground();
            $schedule->command('jav:sync 141jav --type=new')->everyMinute()->runInBackground();
            $schedule->command('jav:sync ffjav --type=new')->everyMinute()->runInBackground();

            $schedule->command('jav:sync onejav --type=daily')->dailyAt('00:00')->runInBackground();
            $schedule->command('jav:sync 141jav --type=daily')->dailyAt('00:00')->runInBackground();
            $schedule->command('jav:sync ffjav --type=daily')->dailyAt('00:00')->runInBackground();

            $schedule->command('jav:sync onejav --type=popular')->dailyAt('00:00')->runInBackground();
            $schedule->command('jav:sync 141jav --type=popular')->dailyAt('00:00')->runInBackground();
            $schedule->command('jav:sync ffjav --type=popular')->dailyAt('00:00')->runInBackground();

            $schedule->command('jav:sync onejav --type=tags')->weeklyOn(0, '00:00')->runInBackground();
            $schedule->command('jav:sync 141jav --type=tags')->weeklyOn(0, '00:00')->runInBackground();
            $schedule->command('jav:sync ffjav --type=tags')->weeklyOn(0, '00:00')->runInBackground();

            $schedule->command('jav:idols:sync-xcity --concurrency=3')->everyMinute()->runInBackground();
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
