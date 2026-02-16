<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'obs' => [
        'enabled' => (bool) env('OBS_ENABLED', false),
        'base_url' => env('OBS_BASE_URL'),
        'api_key' => env('OBS_API_KEY'),
        'timeout_seconds' => (float) env('OBS_TIMEOUT_SECONDS', 2),
        'retry_times' => (int) env('OBS_RETRY_TIMES', 3),
        'retry_sleep_ms' => (int) env('OBS_RETRY_SLEEP_MS', 150),
        'queue_enabled' => (bool) env('OBS_QUEUE_ENABLED', true),
        'queue_name' => env('OBS_QUEUE_NAME', 'obs-telemetry'),
        'service_name' => env('OBS_SERVICE_NAME', 'xcrawlerii'),
        'env' => env('APP_ENV', 'production'),
        'log_level_min' => env('OBS_LOG_LEVEL_MIN', 'info'),
        'max_payload_bytes' => (int) env('OBS_MAX_PAYLOAD_BYTES', 131072),
        'required_response_key' => env('OBS_REQUIRED_RESPONSE_KEY'),
        'success_statuses' => array_map('intval', array_filter(array_map('trim', explode(',', (string) env('OBS_SUCCESS_STATUSES', '200,201,202'))))),
        'non_retryable_statuses' => array_map('intval', array_filter(array_map('trim', explode(',', (string) env('OBS_NON_RETRYABLE_STATUSES', '400,401,403,413,422'))))),
        'dependency_health' => [
            'enabled' => (bool) env('OBS_DEPENDENCY_HEALTH_ENABLED', true),
            'dependencies' => array_filter(array_map('trim', explode(',', (string) env('OBS_DEPENDENCY_HEALTH_DEPENDENCIES', 'mysql,redis,elasticsearch,mongodb')))),
            'schedule_enabled' => (bool) env('OBS_DEPENDENCY_HEALTH_SCHEDULE_ENABLED', false),
            'schedule_cron' => env('OBS_DEPENDENCY_HEALTH_SCHEDULE_CRON', '*/5 * * * *'),
        ],
        'redact_keys' => array_filter(array_map('trim', explode(',', (string) env('OBS_REDACT_KEYS', 'password,token,authorization,cookie,api_key')))),
    ],

];
