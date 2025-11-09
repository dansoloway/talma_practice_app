<?php

require __DIR__.'/../vendor/autoload.php';

$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Get your API key from https://elevenlabs.io/app/settings/api-keys
$apiKey = env('ELEVENLABS_API_KEY') ?: readline('Enter your ElevenLabs API key: ');
$voiceId = 'EXAVITQu4vr4xnSDxMaL'; // Rachel voice (or use your own)

$assets = \App\Models\PromptOptionAsset::whereNull('duration_ms')->get();
$wordOptions = \App\Models\Option::whereNull('word_audio_path')->get();
$prompts = \App\Models\Prompt::whereNull('prompt_audio_path')->get();

echo "Generating " . $assets->count() . " sentence audio files...\n";
echo "Generating " . $wordOptions->count() . " word audio files...\n";
echo "Generating " . $prompts->count() . " prompt audio files...\n\n";

foreach ($assets as $asset) {
    echo "Generating: \"{$asset->generated_sentence}\"\n";
    
    try {
        // Call ElevenLabs API
        $response = \Illuminate\Support\Facades\Http::withHeaders([
            'xi-api-key' => $apiKey,
            'Content-Type' => 'application/json',
        ])->post("https://api.elevenlabs.io/v1/text-to-speech/{$voiceId}", [
            'text' => $asset->generated_sentence,
            'model_id' => 'eleven_monolingual_v1',
            'voice_settings' => [
                'stability' => 0.5,
                'similarity_boost' => 0.75,
            ]
        ]);
        
        if ($response->successful()) {
            // Save the audio file
            $path = str_replace('/storage', '', $asset->audio_path);
            $fullPath = storage_path("app/public{$path}");
            
            // Create directory if needed
            $dir = dirname($fullPath);
            if (!file_exists($dir)) {
                mkdir($dir, 0755, true);
            }
            
            file_put_contents($fullPath, $response->body());
            
            // Update duration (approximate)
            $asset->update(['duration_ms' => 3000]);
            
            echo "  ✓ Saved to: {$fullPath}\n";
        } else {
            echo "  ✗ Error: " . $response->status() . "\n";
        }
        
        // Rate limiting - wait a bit between requests
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
        // Call ElevenLabs API
        $response = \Illuminate\Support\Facades\Http::withHeaders([
            'xi-api-key' => $apiKey,
            'Content-Type' => 'application/json',
        ])->post("https://api.elevenlabs.io/v1/text-to-speech/{$voiceId}", [
            'text' => $prompt->prompt_text,
            'model_id' => 'eleven_monolingual_v1',
            'voice_settings' => [
                'stability' => 0.5,
                'similarity_boost' => 0.75,
            ]
        ]);
        
        if ($response->successful()) {
            // Save the audio file
            $filename = "prompt_{$prompt->id}.mp3";
            $relativePath = "tts/prompts/{$filename}";
            $fullPath = storage_path("app/public/{$relativePath}");
            
            // Create directory if needed
            $dir = dirname($fullPath);
            if (!file_exists($dir)) {
                mkdir($dir, 0755, true);
            }
            
            file_put_contents($fullPath, $response->body());
            
            // Update prompt with audio path
            $prompt->update(['prompt_audio_path' => "/storage/{$relativePath}"]);
            
            echo "  ✓ Saved to: {$fullPath}\n";
        } else {
            echo "  ✗ Error: " . $response->status() . "\n";
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
        // Call ElevenLabs API for word
        $response = \Illuminate\Support\Facades\Http::withHeaders([
            'xi-api-key' => $apiKey,
            'Content-Type' => 'application/json',
        ])->post("https://api.elevenlabs.io/v1/text-to-speech/{$voiceId}", [
            'text' => $option->label,
            'model_id' => 'eleven_monolingual_v1',
            'voice_settings' => [
                'stability' => 0.5,
                'similarity_boost' => 0.75,
            ]
        ]);
        
        if ($response->successful()) {
            // Save the audio file
            $filename = "word_o{$option->id}.mp3";
            $relativePath = "tts/words/{$filename}";
            $fullPath = storage_path("app/public/{$relativePath}");
            
            // Create directory if needed
            $dir = dirname($fullPath);
            if (!file_exists($dir)) {
                mkdir($dir, 0755, true);
            }
            
            file_put_contents($fullPath, $response->body());
            
            // Update option with audio path
            $option->update(['word_audio_path' => "/storage/{$relativePath}"]);
            
            echo "  ✓ Saved to: {$fullPath}\n";
        } else {
            echo "  ✗ Error: " . $response->status() . "\n";
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

