<?php

namespace App\Console\Commands;

use App\Models\Vocabulary;
use Illuminate\Console\Command;
use App\Services\Tts\ElevenLabsTtsService;

class GenerateVocabularyAudio extends Command
{
    protected $signature = 'vocabulary:generate-audio {--force : Regenerate existing audio files}';
    protected $description = 'Generate TTS audio for vocabulary words using ElevenLabs API';

    public function handle()
    {
        $this->info('Generating TTS audio for vocabulary words...');

        // Get TTS service
        $ttsService = app(ElevenLabsTtsService::class);
        
        if (!$ttsService->enabled()) {
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
                $existingPath = public_path(ltrim($vocab->word_audio_path, '/'));
                if (file_exists($existingPath)) {
                    $this->comment("  Audio already exists, skipping");
                    $skipped++;
                    continue;
                }
            }

            try {
                // Use centralized TTS service
                $result = $ttsService->generateAndSaveVocabulary(
                    $vocab->english_word,
                    $this->option('force') ? $vocab->word_audio_path : null, // Delete old if force
                    null // Use default voice
                );
                
                if ($result !== null) {
                    $vocab->update(['word_audio_path' => $result['path']]);
                    $this->info("  ✅ Generated audio: {$result['path']}");
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
}