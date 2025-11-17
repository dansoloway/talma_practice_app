<?php

namespace App\Services\Tts;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ElevenLabsTtsService
{
    private string $apiKey;
    private string $defaultVoiceId;
    
    // High stability presets for clarity and consistency
    private const PRESETS = [
        'vocabulary' => [
            'stability' => 0.8,           // High stability for consistent pronunciation
            'similarity_boost' => 0.85,   // High similarity for clear voice
            'style' => 0.0,               // Neutral style for clarity
            'use_speaker_boost' => true,  // Enhanced clarity
            'speed' => 0.75,              // Slower for learning clarity
        ],
        'sentence' => [
            'stability' => 0.75,          // High stability but slightly more natural
            'similarity_boost' => 0.8,    // Clear voice characteristics
            'style' => 0.1,              // Slightly expressive
            'use_speaker_boost' => true,  // Enhanced clarity
            'speed' => 0.85,              // Slower for comprehension
        ],
    ];

    public function __construct()
    {
        $this->apiKey = config('services.elevenlabs.api_key') ?: env('ELEVENLABS_API_KEY');
        $this->defaultVoiceId = env('ELEVENLABS_DEFAULT_VOICE_ID', 'pNInz6obpgDQGcFmaJgB');
    }

    /**
     * Check if TTS service is enabled
     */
    public function enabled(): bool
    {
        return !empty($this->apiKey);
    }

    /**
     * Generate TTS audio
     * 
     * @param string $text The text to convert to speech
     * @param string $preset 'vocabulary' or 'sentence'
     * @param string|null $voiceId Override default voice ID
     * @return string|null Audio file content, or null on failure
     */
    public function generate(
        string $text,
        string $preset = 'vocabulary',
        ?string $voiceId = null
    ): ?string {
        if (!$this->enabled()) {
            Log::warning('ELEVENLABS_API_KEY not found, skipping TTS generation');
            return null;
        }

        $voiceId = $voiceId ?? $this->defaultVoiceId;
        $settings = self::PRESETS[$preset] ?? self::PRESETS['vocabulary'];

        try {
            $response = Http::withHeaders([
                'Accept' => 'audio/mpeg',
                'Content-Type' => 'application/json',
                'xi-api-key' => $this->apiKey,
            ])->timeout(30)->post(
                "https://api.elevenlabs.io/v1/text-to-speech/{$voiceId}",
                [
                    'text' => $text,
                    'model_id' => 'eleven_multilingual_v2', // Upgraded to v2
                    'voice_settings' => $settings,
                ]
            );

            if ($response->successful()) {
                Log::info("Generated TTS for text: '{$text}' (preset: {$preset})");
                return $response->body();
            } else {
                Log::error("TTS API Error: " . $response->status() . " - " . $response->body());
                return null;
            }
        } catch (\Exception $e) {
            Log::error("TTS generation exception: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Generate TTS for a vocabulary word
     */
    public function generateVocabulary(string $word, ?string $voiceId = null): ?string
    {
        return $this->generate($word, 'vocabulary', $voiceId);
    }

    /**
     * Generate TTS for a sentence
     */
    public function generateSentence(string $sentence, ?string $voiceId = null): ?string
    {
        return $this->generate($sentence, 'sentence', $voiceId);
    }

    /**
     * Get available presets
     */
    public function getPresets(): array
    {
        return self::PRESETS;
    }

    /**
     * Get default voice ID
     */
    public function getDefaultVoiceId(): string
    {
        return $this->defaultVoiceId;
    }
}

