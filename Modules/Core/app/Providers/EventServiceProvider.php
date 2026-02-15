<?php

namespace Modules\Core\Providers;

use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Modules\Core\Listeners\Queue\LogJobFailedListener;
use Modules\Core\Listeners\Queue\LogJobProcessedListener;
use Modules\Core\Listeners\Queue\LogJobStartedListener;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event handler mappings for the application.
     *
     * @var array<string, array<int, string>>
     */
    protected $listen = [
        JobProcessing::class => [
            LogJobStartedListener::class,
        ],
        JobProcessed::class => [
            LogJobProcessedListener::class,
        ],
        JobFailed::class => [
            LogJobFailedListener::class,
        ],
    ];

    /**
     * Indicates if events should be discovered.
     *
     * @var bool
     */
    protected static $shouldDiscoverEvents = true;

    /**
     * Configure the proper event listeners for email verification.
     */
    protected function configureEmailVerification(): void {}
}
