<?php

namespace App\Services\Tts;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

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
            'speed' => 1.0,               // Default speed
        ],
        'sentence' => [
            'stability' => 0.75,          // High stability but slightly more natural
            'similarity_boost' => 0.8,    // Clear voice characteristics
            'style' => 0.1,              // Slightly expressive
            'use_speaker_boost' => true,  // Enhanced clarity
            'speed' => 1.0,               // Default speed
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

    /**
     * Generate and save TTS audio file for vocabulary word
     * This is the complete workflow based on working code
     * 
     * @param string $word The vocabulary word
     * @param string|null $oldAudioPath Old audio path to delete (if regenerating)
     * @param string|null $voiceId Override default voice ID
     * @return array|null Returns ['path' => relative_path, 'full_path' => full_path] or null on failure
     */
    public function generateAndSaveVocabulary(
        string $word,
        ?string $oldAudioPath = null,
        ?string $voiceId = null
    ): ?array {
        // Delete old audio file if provided
        if ($oldAudioPath) {
            $oldRelativePath = ltrim($oldAudioPath, '/');
            $oldRelativePath = preg_replace('#^storage/#', '', $oldRelativePath);
            if (Storage::disk('public')->exists($oldRelativePath)) {
                Storage::disk('public')->delete($oldRelativePath);
                Log::info("Deleted old audio file: {$oldRelativePath}");
            }
        }

        // Generate audio
        $audioData = $this->generateVocabulary($word, $voiceId);
        
        if ($audioData === null) {
            return null;
        }

        // Generate filename and paths
        $filename = 'vocabulary_' . time() . '_' . uniqid() . '.mp3';
        $relativePath = 'tts/vocabulary/' . $filename;
        $fullPath = storage_path("app/public/{$relativePath}");

        // Create directory if needed
        $dir = dirname($fullPath);
        if (!file_exists($dir)) {
            $mkdirResult = @mkdir($dir, 0755, true);
            if (!$mkdirResult && !file_exists($dir)) {
                $error = error_get_last();
                $errorMsg = "Failed to create directory {$dir}: " . ($error['message'] ?? 'Unknown error');
                Log::error($errorMsg);
                throw new \Exception($errorMsg);
            }
        }

        // Check if directory is writable
        if (!is_writable($dir)) {
            $errorMsg = "Directory is not writable: {$dir}";
            Log::error($errorMsg);
            throw new \Exception($errorMsg);
        }

        // Save file
        $saved = @file_put_contents($fullPath, $audioData);
        if ($saved === false) {
            $error = error_get_last();
            $errorMsg = "Failed to save audio file to {$fullPath}: " . ($error['message'] ?? 'Unknown error');
            Log::error($errorMsg);
            throw new \Exception($errorMsg);
        }

        // Verify file was written and is readable
        if (!file_exists($fullPath) || !is_readable($fullPath)) {
            $errorMsg = "File was written but is not readable or doesn't exist: {$fullPath}";
            Log::error($errorMsg);
            throw new \Exception($errorMsg);
        }

        $fileSize = filesize($fullPath);
        Log::info("Successfully generated and saved TTS for '{$word}': {$relativePath} ({$fileSize} bytes)");

        return [
            'path' => "/storage/{$relativePath}",
            'relative_path' => $relativePath,
            'full_path' => $fullPath,
            'size' => $fileSize,
        ];
    }

    /**
     * Generate and save TTS audio file for sentence
     * 
     * @param string $sentence The sentence text
     * @param string $relativePath Relative path where to save (e.g., 'tts/sentences/sentence_123.mp3')
     * @param string|null $oldAudioPath Old audio path to delete (if regenerating)
     * @param string|null $voiceId Override default voice ID
     * @return array|null Returns ['path' => relative_path, 'full_path' => full_path] or null on failure
     */
    public function generateAndSaveSentence(
        string $sentence,
        string $relativePath,
        ?string $oldAudioPath = null,
        ?string $voiceId = null
    ): ?array {
        // Delete old audio file if provided
        if ($oldAudioPath) {
            $oldRelativePath = ltrim($oldAudioPath, '/');
            $oldRelativePath = preg_replace('#^storage/#', '', $oldRelativePath);
            if (Storage::disk('public')->exists($oldRelativePath)) {
                Storage::disk('public')->delete($oldRelativePath);
            }
        }

        // Generate audio
        $audioData = $this->generateSentence($sentence, $voiceId);
        
        if ($audioData === null) {
            return null;
        }

        $fullPath = storage_path("app/public/{$relativePath}");

        // Create directory if needed
        $dir = dirname($fullPath);
        if (!file_exists($dir)) {
            mkdir($dir, 0755, true);
        }

        // Save file
        file_put_contents($fullPath, $audioData);

        // Verify file
        if (!file_exists($fullPath) || !is_readable($fullPath)) {
            throw new \Exception("File was written but is not readable: {$fullPath}");
        }

        return [
            'path' => "/storage/{$relativePath}",
            'relative_path' => $relativePath,
            'full_path' => $fullPath,
            'size' => filesize($fullPath),
        ];
    }
}

