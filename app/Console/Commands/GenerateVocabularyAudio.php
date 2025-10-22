<?php

namespace App\Console\Commands;

use App\Models\Vocabulary;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class GenerateVocabularyAudio extends Command
{
    protected $signature = 'vocabulary:generate-audio {--force : Regenerate existing audio files}';
    protected $description = 'Generate TTS audio for vocabulary words using ElevenLabs API';

    public function handle()
    {
        $this->info('Generating TTS audio for vocabulary words...');

        $apiKey = config('services.elevenlabs.api_key') ?: env('ELEVENLABS_API_KEY');
        if (!$apiKey) {
            $this->error('ELEVENLABS_API_KEY not found in .env file');
            return 1;
        }

        $vocabulary = Vocabulary::where('is_active', true)->get();
        $this->info("Found {$vocabulary->count()} vocabulary items");

        $generated = 0;
        $skipped = 0;
        $errors = 0;

        foreach ($vocabulary as $vocab) {
            $this->info("Processing: {$vocab->english_word}");

            // Check if audio already exists
            if ($vocab->word_audio_path && !$this->option('force')) {
                $this->comment("  Audio already exists, skipping");
                $skipped++;
                continue;
            }

            try {
                $audioPath = $this->generateAudio($vocab->english_word, $apiKey);
                
                if ($audioPath) {
                    $vocab->update(['word_audio_path' => $audioPath]);
                    $this->info("  ✅ Generated audio: {$audioPath}");
                    $generated++;
                } else {
                    $this->error("  ❌ Failed to generate audio");
                    $errors++;
                }
            } catch (\Exception $e) {
                $this->error("  ❌ Error: " . $e->getMessage());
                $errors++;
            }
        }

        $this->info("\nSummary:");
        $this->info("Generated: {$generated}");
        $this->info("Skipped: {$skipped}");
        $this->info("Errors: {$errors}");

        return 0;
    }

    private function generateAudio($text, $apiKey)
    {
        $voiceId = 'pNInz6obpgDQGcFmaJgB'; // Default voice ID
        
        $response = Http::withHeaders([
            'Accept' => 'audio/mpeg',
            'Content-Type' => 'application/json',
            'xi-api-key' => $apiKey,
        ])->post("https://api.elevenlabs.io/v1/text-to-speech/{$voiceId}", [
            'text' => $text,
            'model_id' => 'eleven_monolingual_v1',
            'voice_settings' => [
                'stability' => 0.5,
                'similarity_boost' => 0.5
            ]
        ]);

        if ($response->successful()) {
            $filename = 'vocabulary_' . time() . '_' . uniqid() . '.mp3';
            $path = 'vocabulary-audio/' . $filename;
            
            Storage::disk('public')->put($path, $response->body());
            
            return $path;
        }

        throw new \Exception("API request failed: " . $response->body());
    }
}