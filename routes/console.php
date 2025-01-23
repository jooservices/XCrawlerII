<?php

use Modules\Client\Repositories\RequestLogRepository;

\Illuminate\Support\Facades\Schedule::command('jav:onejav-sync daily')->daily();
\Illuminate\Support\Facades\Schedule::command('jav:onejav-sync new')->everyMinute();
\Illuminate\Support\Facades\Schedule::command('jav:onejav-sync popular')->everyThirtyMinutes();
\Illuminate\Support\Facades\Schedule::command('jav:onejav-sync tags')->weekly();

\Illuminate\Support\Facades\Schedule::command('jav:missav-sync recentUpdate')->daily();

Schedule::call(static function () {
    app(RequestLogRepository::class)->cleanup();
})->daily();
