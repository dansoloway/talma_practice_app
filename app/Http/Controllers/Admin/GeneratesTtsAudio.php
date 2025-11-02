<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

trait GeneratesTtsAudio
{
    /**
     * Generate TTS for a single word option.
     */
    protected function generateSingleWordTts($option)
    {
        Log::info("Starting TTS generation for word: '{$option->label}' (Option ID: {$option->id})");
        
        // Check if audio already exists for this option
        if ($option->word_audio_path) {
            $fullPath = public_path(ltrim($option->word_audio_path, '/'));
            Log::info("Checking existing audio path: {$fullPath}");
            if (file_exists($fullPath)) {
                Log::info("Word TTS already exists for option: {$option->label}");
                return; // Skip generation
            } else {
                Log::warning("Audio path exists in DB but file not found: {$fullPath}");
            }
        }

        // Check if we already have TTS for this exact word from another option
        $existingOption = \App\Models\Option::where('label', $option->label)
            ->whereNotNull('word_audio_path')
            ->where('id', '!=', $option->id)
            ->first();

        if ($existingOption && $existingOption->word_audio_path) {
            $existingPath = public_path(ltrim($existingOption->word_audio_path, '/'));
            if (file_exists($existingPath)) {
                // Copy the existing file to a new location for this option
                $filename = "word_o{$option->id}.mp3";
                $relativePath = "tts/words/{$filename}";
                $newPath = storage_path("app/public/{$relativePath}");
                
                // Create directory if needed
                $dir = dirname($newPath);
                if (!file_exists($dir)) {
                    mkdir($dir, 0755, true);
                }
                
                copy($existingPath, $newPath);
                $option->update(['word_audio_path' => "/storage/{$relativePath}"]);
                
                Log::info("Reused existing TTS for word: {$option->label}");
                return; // Skip API generation
            }
        }

        $apiKey = env('ELEVENLABS_API_KEY');
        
        if (!$apiKey) {
            Log::error('ELEVENLABS_API_KEY not found in environment');
            throw new \Exception('ELEVENLABS_API_KEY not found');
        }

        $voiceId = 'EXAVITQu4vr4xnSDxMaL'; // Rachel voice
        Log::info("Making API call to ElevenLabs for word: '{$option->label}'");
        
        // Log to dedicated TTS file
        $ttsLogFile = storage_path('logs/tts_generation.log');
        file_put_contents($ttsLogFile, "[" . now() . "] Making API call for word: '{$option->label}'\n", FILE_APPEND);

        $response = Http::withHeaders([
            'xi-api-key' => $apiKey,
            'Content-Type' => 'application/json',
        ])->timeout(30)->post("https://api.elevenlabs.io/v1/text-to-speech/{$voiceId}", [
            'text' => $option->label,
            'model_id' => 'eleven_monolingual_v1',
            'voice_settings' => [
                'stability' => 0.5,
                'similarity_boost' => 0.75,
            ]
        ]);
        
        Log::info("API response status: " . $response->status());
        if (!$response->successful()) {
            Log::error("API error response: " . $response->body());
        }
        
        if ($response->successful()) {
            // Save the audio file
            $filename = "word_o{$option->id}.mp3";
            $relativePath = "tts/words/{$filename}";
            $fullPath = storage_path("app/public/{$relativePath}");
            
            Log::info("Saving audio file to: {$fullPath}");
            
            // Create directory if needed
            $dir = dirname($fullPath);
            if (!file_exists($dir)) {
                Log::info("Creating directory: {$dir}");
                mkdir($dir, 0755, true);
            }
            
            $audioData = $response->body();
            Log::info("Audio data size: " . strlen($audioData) . " bytes");
            
            $saved = file_put_contents($fullPath, $audioData);
            if ($saved === false) {
                Log::error("Failed to save audio file to: {$fullPath}");
                throw new \Exception("Failed to save audio file");
            }
            
            Log::info("Successfully saved audio file, bytes written: {$saved}");
            
            // Update option with audio path
            $dbPath = "/storage/{$relativePath}";
            Log::info("Updating option {$option->id} with audio path: {$dbPath}");
            $option->update(['word_audio_path' => $dbPath]);
            
            Log::info("Generated TTS for option: {$option->label}");
        } else {
            Log::error("TTS API Error: " . $response->status() . " - " . $response->body());
            throw new \Exception("TTS API Error: " . $response->status());
        }
    }

    /**
     * Generate TTS for a single sentence combination.
     */
    protected function generateSingleSentenceTts($option)
    {
        // Get the prompt and complete sentence
        $prompt = $option->prompt;
        $completeSentence = str_replace('{}', $option->label, $prompt->template);

        // Check if sentence audio already exists for this option
        if ($option->sentence_audio_path) {
            $fullPath = public_path(ltrim($option->sentence_audio_path, '/'));
            if (file_exists($fullPath)) {
                Log::info("Sentence TTS already exists: {$completeSentence}");
                return; // Skip generation
            }
        }

        // Check if we already have TTS for this exact sentence from another option
        // This happens when the same word is used in the same template
        $existingOption = \App\Models\Option::whereHas('prompt', function($query) use ($prompt) {
                $query->where('template', $prompt->template);
            })
            ->where('label', $option->label)
            ->whereNotNull('sentence_audio_path')
            ->where('id', '!=', $option->id)
            ->first();

        if ($existingOption && $existingOption->sentence_audio_path) {
            $existingPath = public_path(ltrim($existingOption->sentence_audio_path, '/'));
            if (file_exists($existingPath)) {
                // Copy the existing file to a new location for this option
                $filename = "sentence_p{$prompt->id}_o{$option->id}.mp3";
                $relativePath = "tts/sentences/{$filename}";
                $newPath = storage_path("app/public/{$relativePath}");
                
                // Create directory if needed
                $dir = dirname($newPath);
                if (!file_exists($dir)) {
                    mkdir($dir, 0755, true);
                }
                
                copy($existingPath, $newPath);
                $option->update(['sentence_audio_path' => "/storage/{$relativePath}"]);
                
                Log::info("Reused existing sentence TTS: {$completeSentence}");
                return; // Skip API generation
            }
        }

        $apiKey = env('ELEVENLABS_API_KEY');
        
        if (!$apiKey) {
            throw new \Exception('ELEVENLABS_API_KEY not found');
        }

        $voiceId = 'EXAVITQu4vr4xnSDxMaL'; // Rachel voice

        // Log to dedicated TTS file
        $ttsLogFile = storage_path('logs/tts_generation.log');
        file_put_contents($ttsLogFile, "[" . now() . "] Making API call for sentence: '{$completeSentence}'\n", FILE_APPEND);

        $response = Http::withHeaders([
            'xi-api-key' => $apiKey,
            'Content-Type' => 'application/json',
        ])->timeout(30)->post("https://api.elevenlabs.io/v1/text-to-speech/{$voiceId}", [
            'text' => $completeSentence,
            'model_id' => 'eleven_monolingual_v1',
            'voice_settings' => [
                'stability' => 0.5,
                'similarity_boost' => 0.75,
            ]
        ]);
        
        // Log API response
        file_put_contents($ttsLogFile, "[" . now() . "] API response status: " . $response->status() . " for sentence: '{$completeSentence}'\n", FILE_APPEND);
        
        if ($response->successful()) {
            // Save the audio file with a unique name
            $filename = "sentence_p{$prompt->id}_o{$option->id}.mp3";
            $relativePath = "tts/sentences/{$filename}";
            $fullPath = storage_path("app/public/{$relativePath}");
            
            // Create directory if needed
            $dir = dirname($fullPath);
            if (!file_exists($dir)) {
                mkdir($dir, 0755, true);
            }
            
            file_put_contents($fullPath, $response->body());
            
            // Store the sentence audio path in the option
            $option->update(['sentence_audio_path' => "/storage/{$relativePath}"]);
            
            Log::info("Generated sentence TTS: {$completeSentence}");
        } else {
            Log::error("Sentence TTS API Error: " . $response->status() . " - " . $response->body());
            throw new \Exception("Sentence TTS API Error: " . $response->status());
        }
    }
}

