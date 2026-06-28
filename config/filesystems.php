<?php

return [

    'default' => env('FILESYSTEM_DISK', 'local'),

    'disks' => [

        'local' => [
            'driver' => 'local',
            'root' => storage_path('app'),
            'throw' => false,
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => env('APP_URL').'/storage',
            'visibility' => 'public',
            'throw' => false,
        ],

        'voice_training' => [
            'driver' => env('VOICE_TRAINING_DISK_DRIVER', 'local'),
            // Only used for local driver. On S3, root becomes an object key prefix — leave empty.
            'root' => env('VOICE_TRAINING_DISK_DRIVER', 'local') === 's3'
                ? ''
                : storage_path('app/voice-training'),
            'throw' => true,
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_VOICE_TRAINING_BUCKET'),
            'visibility' => 'private',
        ],

    ],

    'voice_training_disk' => env('VOICE_TRAINING_DISK', 'voice_training'),

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],

];

