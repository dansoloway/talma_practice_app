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

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'unsplash' => [
        'access_key' => env('UNSPLASH_ACCESS_KEY'),
    ],

'pixabay' => [
    'api_key' => env('PIXABAY_API_KEY'),
],

'flaticon' => [
    'api_key' => env('FLATICON_API_KEY'),
],

'freepik' => [
    'api_key' => env('FREEPIK_API_KEY'),
],

'image' => [
    // Comma-separated priority: iconify, stock, leonardo, openai, flaticon, freepik
    'providers' => env('IMAGE_PROVIDERS', 'iconify,stock,leonardo,openai'),
    'iconify_enabled' => env('IMAGE_ICONIFY_ENABLED', true),
    'iconify_size' => env('IMAGE_ICONIFY_SIZE', 512),
],

    'elevenlabs' => [
        'api_key' => env('ELEVENLABS_API_KEY'),
    ],

'openai' => [
    'key' => env('OPENAI_API_KEY'),
    'translation_model' => env('OPENAI_TRANSLATION_MODEL', 'gpt-4o-mini'),
    'arabic_variant' => env('OPENAI_ARABIC_VARIANT', 'saudi'), // saudi | msa
    'fallback_model' => env('OPENAI_FALLBACK_MODEL', 'gpt-4o'), // Used when primary model fails validation
    'endpoint' => env('OPENAI_ENDPOINT', 'https://api.openai.com/v1/chat/completions'),
    'rate_limit_delay' => env('OPENAI_RATE_LIMIT_DELAY', null), // Seconds between requests (null = auto-detect from API)
    'image_model' => env('OPENAI_IMAGE_MODEL', 'dall-e-3'),
    'image_size' => env('OPENAI_IMAGE_SIZE', '1024x1024'), // Options: 1024x1024, 1024x1792, 1792x1024
],

'leonardo' => [
    'api_key' => env('LEONARDO_API_KEY'),
    'model' => env('LEONARDO_MODEL', 'leonardo-flash-xl'), // Options: leonardo-flash-xl, leonardo-vision-xl, etc.
    'size' => env('LEONARDO_SIZE', '1024x1024'), // Options: 512x512, 768x768, 1024x1024, etc.
],

];
