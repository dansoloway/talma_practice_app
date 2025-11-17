<?php

require __DIR__.'/../vendor/autoload.php';

$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Get TTS service
$ttsService = app(\App\Services\Tts\ElevenLabsTtsService::class);

if (!$ttsService->enabled()) {
    echo "❌ ELEVENLABS_API_KEY not found in .env file\n";
    exit(1);
}

$voiceId = 'EXAVITQu4vr4xnSDxMaL'; // Rachel voice

$assets = \App\Models\PromptOptionAsset::whereNull('duration_ms')->get();
$wordOptions = \App\Models\Option::whereNull('word_audio_path')->get();
$prompts = \App\Models\Prompt::whereNull('prompt_audio_path')->get();

echo "Generating " . $assets->count() . " sentence audio files...\n";
echo "Generating " . $wordOptions->count() . " word audio files...\n";
echo "Generating " . $prompts->count() . " prompt audio files...\n\n";

// Generate sentence audio for assets
foreach ($assets as $asset) {
    echo "Generating: \"{$asset->generated_sentence}\"\n";
    
    try {
        $relativePath = str_replace('/storage/', '', $asset->audio_path);
        $relativePath = str_replace('storage/', '', $relativePath);
        
        // Use centralized TTS service
        $result = $ttsService->generateAndSaveSentence(
            $asset->generated_sentence,
            $relativePath,
            null, // No old path to delete
            $voiceId
        );
        
        if ($result !== null) {
            // Update duration (approximate)
            $asset->update(['duration_ms' => 3000]);
            echo "  ✓ Saved to: {$result['full_path']}\n";
        } else {
            echo "  ✗ Failed to generate audio\n";
        }
        
        // Rate limiting
        usleep(500000); // 0.5 seconds
        
    } catch (Exception $e) {
        echo "  ✗ Error: " . $e->getMessage() . "\n";
    }
    
    echo "\n";
}

echo "\n=== Generating Prompt Audio ===\n\n";

foreach ($prompts as $prompt) {
    echo "Generating prompt: \"{$prompt->prompt_text}\"\n";
    
    try {
        $filename = "prompt_{$prompt->id}.mp3";
        $relativePath = "tts/prompts/{$filename}";
        
        // Use centralized TTS service
        $result = $ttsService->generateAndSaveSentence(
            $prompt->prompt_text,
            $relativePath,
            null, // No old path to delete
            $voiceId
        );
        
        if ($result !== null) {
            $prompt->update(['prompt_audio_path' => $result['path']]);
            echo "  ✓ Saved to: {$result['full_path']}\n";
        } else {
            echo "  ✗ Failed to generate audio\n";
        }
        
        // Rate limiting
        usleep(500000); // 0.5 seconds
        
    } catch (Exception $e) {
        echo "  ✗ Error: " . $e->getMessage() . "\n";
    }
    
    echo "\n";
}

echo "\n=== Generating Word Audio ===\n\n";

foreach ($wordOptions as $option) {
    echo "Generating word: \"{$option->label}\"\n";
    
    try {
        // Use centralized TTS service
        $result = $ttsService->generateAndSaveVocabulary(
            $option->label,
            null, // No old path to delete
            $voiceId
        );
        
        if ($result !== null) {
            $option->update(['word_audio_path' => $result['path']]);
            echo "  ✓ Saved to: {$result['full_path']}\n";
        } else {
            echo "  ✗ Failed to generate audio\n";
        }
        
        // Rate limiting
        usleep(500000); // 0.5 seconds
        
    } catch (Exception $e) {
        echo "  ✗ Error: " . $e->getMessage() . "\n";
    }
    
    echo "\n";
}

echo "Done!\n";
echo "Run: php artisan tts:verify\n";

