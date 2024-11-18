<?php

namespace App\Providers;

use App\Exceptions\MySQLConnectionIssue;
use App\Exceptions\RedisConnectionIssue;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        try {
            DB::connection()->getPdo();
        } catch (Exception $e) {
            throw new MySQLConnectionIssue($e->getMessage());
        }

        try {
            $redis = Redis::connection();
            if (!$redis->client()->isConnected()) {
                throw new RedisConnectionIssue();
            }
            $redis->disconnect();
        } catch (Exception $e) {
            throw new RedisConnectionIssue($e->getMessage());
        }
    }
}
