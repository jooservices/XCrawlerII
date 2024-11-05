<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

\Illuminate\Support\Facades\Schedule::command('jav:onejav-sync daily')->daily();
\Illuminate\Support\Facades\Schedule::command('jav:onejav-sync new')->everyMinute();
\Illuminate\Support\Facades\Schedule::command('jav:onejav-sync popular')->everyThirtyMinutes();
