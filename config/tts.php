<?php

return [
    /*
    |--------------------------------------------------------------------------
    | TTS Settings Manager Configuration
    |--------------------------------------------------------------------------
    |
    | Centralized configuration for Text-to-Speech settings optimized for
    | single English vocabulary words in an educational context.
    |
    */

    // Default model — eleven_monolingual_v1 was deprecated by ElevenLabs (2025+)
    'default_model_id' => env('ELEVENLABS_MODEL', 'eleven_multilingual_v2'),

    // Default voice ID (Adam voice)
    'default_voice_id' => env('ELEVENLABS_DEFAULT_VOICE_ID', 'pNInz6obpgDQGcFmaJgB'),

    // Content type presets
    'presets' => [
        'vocabulary' => [
            // Baseline values for consistent, clear pronunciation
            'stability' => (float) env('ELEVENLABS_VOCAB_STABILITY', 0.90),
            'similarity_boost' => (float) env('ELEVENLABS_VOCAB_SIMILARITY', 0.85),
            'style' => (float) env('ELEVENLABS_VOCAB_STYLE', 0.0),
            'speed' => (float) env('ELEVENLABS_VOCAB_SPEED', 0.92),
            'use_speaker_boost' => env('ELEVENLABS_VOCAB_SPEAKER_BOOST', true) === true || env('ELEVENLABS_VOCAB_SPEAKER_BOOST', true) === 'true',
        ],
        'sentence' => [
            // For sentences (if needed in future)
            'stability' => (float) env('ELEVENLABS_SENTENCE_STABILITY', 0.75),
            'similarity_boost' => (float) env('ELEVENLABS_SENTENCE_SIMILARITY', 0.8),
            'style' => (float) env('ELEVENLABS_SENTENCE_STYLE', 0.1),
            'speed' => (float) env('ELEVENLABS_SENTENCE_SPEED', 1.0),
            'use_speaker_boost' => env('ELEVENLABS_SENTENCE_SPEAKER_BOOST', true) === true || env('ELEVENLABS_SENTENCE_SPEAKER_BOOST', true) === 'true',
        ],
    ],

    // Length-based presets (merged over vocabulary defaults)
    'length_presets' => [
        'short_words' => [
            'speed' => 0.95, // Slightly faster for short words (optimized)
        ],
        'medium_words' => [
            // Uses vocabulary defaults (speed: 0.92)
        ],
        'long_words' => [
            'speed' => 0.88, // Slower for clarity on long words (optimized range: 0.88-0.90)
        ],
    ],

    // Per-word overrides (highest priority)
    // Format: 'word' => ['setting' => value, ...]
    'word_overrides' => [
        'environment' => [
            'speed' => 0.88,
        ],
        'students' => [
            'stability' => 0.92,
        ],
        // Add more problematic words here as discovered
    ],

    // SSML/Phoneme overrides for words with pronunciation issues
    // Format: 'word' => '<phoneme alphabet="cmu-arpabet" ph="PHONEMES">word</phoneme>'
    'phoneme_overrides' => [
        // Example: 'colonel' => '<phoneme alphabet="cmu-arpabet" ph="K ER N AH L">colonel</phoneme>',
        // Add words with known pronunciation issues here
    ],

    // Word classification rules
    'classification' => [
        'short' => [
            'max_chars' => 4,
            'max_syllables' => 1,
        ],
        'medium' => [
            'min_chars' => 5,
            'max_chars' => 8,
            'min_syllables' => 2,
            'max_syllables' => 3,
        ],
        'long' => [
            'min_chars' => 9,
            'min_syllables' => 4,
        ],
    ],

    // Telemetry/logging settings
    'telemetry' => [
        'enabled' => env('TTS_TELEMETRY_ENABLED', true),
        'log_to_file' => env('TTS_LOG_TO_FILE', true),
        'log_to_database' => env('TTS_LOG_TO_DATABASE', false), // Future: DB logging
        'log_file' => storage_path('logs/tts_telemetry.log'),
    ],
];
