<?php

namespace App\Http\Controllers\Admin;

use App\Services\Tts\ElevenLabsTtsService;
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

        $ttsService = app(ElevenLabsTtsService::class);
        
        if (!$ttsService->enabled()) {
            Log::error('ELEVENLABS_API_KEY not found in environment');
            throw new \Exception('ELEVENLABS_API_KEY not found');
        }

        Log::info("Making API call to ElevenLabs for word: '{$option->label}'");
        
        // Log to dedicated TTS file
        $ttsLogFile = storage_path('logs/tts_generation.log');
        file_put_contents($ttsLogFile, "[" . now() . "] Making API call for word: '{$option->label}'\n", FILE_APPEND);

        // Use centralized TTS service method that handles everything
        $result = $ttsService->generateAndSaveVocabulary(
            $option->label,
            $option->word_audio_path, // Old path to delete if regenerating
            'EXAVITQu4vr4xnSDxMaL' // Rachel voice
        );
        
        if ($result !== null) {
            // Update option with audio path
            $option->update(['word_audio_path' => $result['path']]);
            Log::info("Generated TTS for option: {$option->label} (path: {$result['path']}, size: {$result['size']} bytes)");
        } else {
            Log::error("TTS generation failed for word: {$option->label}");
            throw new \Exception("TTS generation failed");
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

        $legacyAsset = \App\Models\PromptOptionAsset::query()
            ->where('prompt_id', $option->prompt_id)
            ->where('option_id', $option->id)
            ->first();

        if ($legacyAsset?->audio_path) {
            $legacyPath = public_path(ltrim($legacyAsset->audio_path, '/'));
            if (file_exists($legacyPath)) {
                $filename = "sentence_p{$prompt->id}_o{$option->id}.mp3";
                $relativePath = "tts/sentences/{$filename}";
                $newPath = storage_path("app/public/{$relativePath}");

                $dir = dirname($newPath);
                if (! file_exists($dir)) {
                    mkdir($dir, 0755, true);
                }

                if ($legacyPath !== $newPath) {
                    copy($legacyPath, $newPath);
                }

                $option->update(['sentence_audio_path' => "/storage/{$relativePath}"]);
                Log::info("Reused legacy sentence TTS asset: {$completeSentence}");

                return;
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

        $ttsService = app(ElevenLabsTtsService::class);
        
        if (!$ttsService->enabled()) {
            throw new \Exception('ELEVENLABS_API_KEY not found');
        }

        // Log to dedicated TTS file
        $ttsLogFile = storage_path('logs/tts_generation.log');
        file_put_contents($ttsLogFile, "[" . now() . "] Making API call for sentence: '{$completeSentence}'\n", FILE_APPEND);

        // Generate filename and path
        $filename = "sentence_p{$prompt->id}_o{$option->id}.mp3";
        $relativePath = "tts/sentences/{$filename}";
        
        // Use centralized TTS service method that handles everything
        $result = $ttsService->generateAndSaveSentence(
            $completeSentence,
            $relativePath,
            $option->sentence_audio_path, // Old path to delete if regenerating
            'EXAVITQu4vr4xnSDxMaL' // Rachel voice
        );
        
        // Log API response
        file_put_contents($ttsLogFile, "[" . now() . "] TTS generation " . ($result !== null ? "successful" : "failed") . " for sentence: '{$completeSentence}'\n", FILE_APPEND);
        
        if ($result !== null) {
            // Store the sentence audio path in the option
            $option->update(['sentence_audio_path' => $result['path']]);
            Log::info("Generated sentence TTS: {$completeSentence} (path: {$result['path']}, size: {$result['size']} bytes)");
        } else {
            Log::error("Sentence TTS generation failed: {$completeSentence}");
            throw new \Exception("Sentence TTS generation failed");
        }
    }
}

