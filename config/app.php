<?php

use Illuminate\Support\Facades\Facade;
use Illuminate\Support\ServiceProvider;

return [

    'name' => env('APP_NAME', 'TALMA Practice Pal'),
    'env' => env('APP_ENV', 'production'),
    'debug' => (bool) env('APP_DEBUG', false),
    'url' => env('APP_URL', 'http://localhost'),
    'asset_url' => env('ASSET_URL'),
    'timezone' => 'UTC',
    'locale' => 'en',
    'fallback_locale' => 'en',
    'faker_locale' => 'en_US',
    'key' => env('APP_KEY'),
    'cipher' => 'AES-256-CBC',
    
    'allow_recording_upload' => env('PRIVACY_ALLOW_UPLOAD', false),
    'recording_max_seconds' => env('RECORDING_MAX_SECONDS', 20),
    'speech_feedback_enabled' => env('SPEECH_FEEDBACK_ENABLED', true),
    'voice_waiver_text' => env('VOICE_WAIVER_TEXT', 'I agree that my voice recordings may be saved anonymously to help improve voice recognition tools.'),
    'voice_sample_viewer_emails' => array_values(array_filter(array_map(
        'strtolower',
        explode(',', env('VOICE_SAMPLE_VIEWER_EMAILS', ''))
    ))),
    'practice_session_cookie' => env('PRACTICE_SESSION_COOKIE', 'talma_session_id'),

    'summer_daily_report_emails' => array_values(array_filter(array_map(
        'trim',
        explode(',', env('SUMMER_DAILY_REPORT_EMAILS', 'daniel@talmaisrael.com'))
    ))),
    'summer_daily_report_timezone' => env('SUMMER_DAILY_REPORT_TIMEZONE', 'Asia/Jerusalem'),

    'maintenance' => [
        'driver' => 'file',
    ],

    'providers' => ServiceProvider::defaultProviders()->merge([
        App\Providers\AppServiceProvider::class,
    ])->toArray(),

    'aliases' => Facade::defaultAliases()->merge([
        // 'Example' => App\Facades\Example::class,
    ])->toArray(),

];

