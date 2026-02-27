<?php

namespace Modules\Core\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Log as LogFacade;
use Illuminate\Support\ServiceProvider;
use JOOservices\Client\Cache\MemoryCache;
use Modules\Core\Console\Commands\ServicesHealthCheck;
use Modules\Core\Models\Log as LogModel;
use Modules\Core\Repositories\LogRepository;
use Modules\Core\Services\Client\Client;
use Modules\Core\Services\LogService;
use Modules\Core\Services\Client\Contracts\ClientContract;
use Modules\Core\Services\Client\Logging\HttpLogSanitizer;
use Modules\Core\Services\Client\Logging\MongoHttpLogWriter;
use Modules\Core\Support\Logging\LogMongoFormatter;
use MongoDB\Driver\Manager;
use Monolog\Handler\MongoDBHandler;
use Monolog\Level;
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
        $this->registerTranslations();
        $this->registerConfig();
        $this->registerViews();
        $this->loadMigrationsFrom(module_path($this->name, 'database/migrations'));
        $this->registerMongoLogDriver();
    }

    private function registerMongoLogDriver(): void
    {
        LogFacade::extend('mongodb', function ($app, array $config) {
            $dsn = (string) config('database.connections.mongodb.dsn', env('MONGO_URI', 'mongodb://127.0.0.1:27017'));
            $database = (string) config('database.connections.mongodb.database', env('MONGO_DB', 'xcrawler'));
            $collection = LogModel::COLLECTION;
            $level = $config['level'] ?? 'debug';
            $level = is_string($level) ? Level::fromName($level) : $level;

            $manager = new Manager($dsn);
            $handler = new MongoDBHandler($manager, $database, $collection, $level, true);
            $handler->setFormatter($app->make(LogMongoFormatter::class));

            $logger = new \Monolog\Logger($config['name'] ?? 'mongodb');
            $logger->pushHandler($handler);

            return $logger;
        });
    }

    /**
     * Register the service provider.
     */
    public function register(): void
    {
        $this->app->register(EventServiceProvider::class);
        $this->app->register(RouteServiceProvider::class);

        $this->app->singleton(HttpLogSanitizer::class, function (): HttpLogSanitizer {
            return new HttpLogSanitizer(
                previewBytes: (int) env('XCRAWLER_LOG_PREVIEW_BYTES', 8192),
            );
        });

        $this->app->singleton(MongoHttpLogWriter::class, function (): MongoHttpLogWriter {
            return new MongoHttpLogWriter(
                uri: (string) env('MONGO_URI', 'mongodb://127.0.0.1:27017'),
                database: (string) env('MONGO_DB', 'xcrawler'),
            );
        });

        $this->app->singleton(MemoryCache::class, fn (): MemoryCache => new MemoryCache);
        $this->app->singleton(LogMongoFormatter::class, fn (): LogMongoFormatter => new LogMongoFormatter);

        $this->app->singleton(LogRepository::class);
        $this->app->singleton(LogService::class);

        $this->app->singleton(\Modules\Core\Repositories\Contracts\ConfigRepositoryInterface::class, \Modules\Core\Repositories\ConfigRepository::class);
        $this->app->singleton(\Modules\Core\Services\ConfigService::class);
        $this->app->alias(\Modules\Core\Services\ConfigService::class, 'core.config');

        $this->app->bind(ClientContract::class, function ($app): ClientContract {
            $cacheStore = (string) env('CACHE_STORE', 'database');

            return new Client(
                sanitizer: $app->make(HttpLogSanitizer::class),
                logWriter: $app->make(MongoHttpLogWriter::class),
                cache: $app->make(MemoryCache::class),
                timeoutSec: (int) env('XCRAWLER_CLIENT_TIMEOUT', 20),
                connectTimeoutSec: (int) env('XCRAWLER_CLIENT_CONNECT_TIMEOUT', 8),
                defaultMaxAttempts: (int) env('XCRAWLER_CLIENT_MAX_ATTEMPTS', 3),
                defaultCacheTtlSec: (int) env('XCRAWLER_CLIENT_CACHE_TTL', 300),
                cacheStore: $cacheStore,
            );
        });
    }

    /**
     * Register commands in the format of Command::class
     */
    protected function registerCommands(): void
    {
        $this->commands([
            ServicesHealthCheck::class,
        ]);
    }

    /**
     * Register command Schedules.
     */
    protected function registerCommandSchedules(): void
    {
        // $this->app->booted(function () {
        //     $schedule = $this->app->make(Schedule::class);
        //     $schedule->command('inspire')->hourly();
        // });
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
