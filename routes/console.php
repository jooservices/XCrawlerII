<?php

\Illuminate\Support\Facades\Schedule::command('jav:onejav-sync daily')->daily();
\Illuminate\Support\Facades\Schedule::command('jav:onejav-sync new')->everyMinute();
\Illuminate\Support\Facades\Schedule::command('jav:onejav-sync popular')->everyThirtyMinutes();
\Illuminate\Support\Facades\Schedule::command('jav:onejav-sync tags')->everyFiveMinutes();
