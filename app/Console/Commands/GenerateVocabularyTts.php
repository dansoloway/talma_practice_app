<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Vocabulary;
use Illuminate\Support\Facades\Http;

class GenerateVocabularyTts extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'talma:generate-vocab-tts {--lesson= : Generate TTS for specific lesson ID} {--force : Regenerate even if audio already exists}';

    /**
     * The list of command aliases.
     *
     * @var array<int, string>
     */
    protected $aliases = ['wespeak:generate-vocab-tts'];

    /**
     * The console command description.
     */
    protected $description = 'Generate TTS audio files for vocabulary words';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🎵 TALMA Practice Pal Vocabulary TTS Generator');
        $this->info('===================================');

        // Get vocabulary words
        $query = Vocabulary::query();
        
        if ($this->option('lesson')) {
            $query->where('lesson_id', $this->option('lesson'));
            $this->info('Generating TTS for lesson ID: ' . $this->option('lesson'));
        } else {
            $this->info('Generating TTS for ALL vocabulary words');
        }

        $vocabularyWords = $query->orderBy('lesson_id')->orderBy('sort_order')->get();

        if ($vocabularyWords->isEmpty()) {
            $this->warn('No vocabulary words found.');
            return 0;
        }

        $this->info("Found {$vocabularyWords->count()} vocabulary words");
        $this->newLine();

        // Check API key
        $apiKey = env('ELEVENLABS_API_KEY');
        if (!$apiKey) {
            $this->error('❌ ELEVENLABS_API_KEY not found in .env file');
            return 1;
        }

        $voiceId = 'EXAVITQu4vr4xnSDxMaL'; // Rachel voice
        $generated = 0;
        $skipped = 0;
        $errors = 0;

        // Progress bar
        $bar = $this->output->createProgressBar($vocabularyWords->count());
        $bar->start();

        foreach ($vocabularyWords as $vocab) {
            $bar->advance();

            // Skip if audio already exists (unless force flag is used)
            if ($vocab->word_audio_path && !$this->option('force')) {
                $existingPath = public_path(ltrim($vocab->word_audio_path, '/'));
                if (file_exists($existingPath)) {
                    $skipped++;
                    continue;
                }
            }

            try {
                // Generate TTS
                $response = Http::withHeaders([
                    'xi-api-key' => $apiKey,
                    'Content-Type' => 'application/json',
                ])->timeout(30)->post("https://api.elevenlabs.io/v1/text-to-speech/{$voiceId}", [
                    'text' => $vocab->english_word,
                    'model_id' => 'eleven_monolingual_v1',
                    'voice_settings' => [
                        'stability' => 0.5,
                        'similarity_boost' => 0.75,
                    ]
                ]);

                if ($response->successful()) {
                    // Save audio file
                    $filename = "vocab_" . $vocab->id . "_" . time() . ".mp3";
                    $relativePath = "vocabulary-audio/{$filename}";
                    $fullPath = storage_path("app/public/{$relativePath}");

                    // Create directory if needed
                    $dir = dirname($fullPath);
                    if (!file_exists($dir)) {
                        mkdir($dir, 0755, true);
                    }

                    file_put_contents($fullPath, $response->body());

                    // Update vocabulary record (without /storage/ prefix - Laravel's asset() will add it)
                    $vocab->update(['word_audio_path' => $relativePath]);

                    $generated++;
                } else {
                    $this->newLine();
                    $this->error("Failed to generate TTS for: {$vocab->english_word} (HTTP {$response->status()})");
                    $errors++;
                }

                // Small delay to avoid rate limiting
                usleep(200000); // 0.2 seconds

            } catch (\Exception $e) {
                $this->newLine();
                $this->error("Error generating TTS for {$vocab->english_word}: " . $e->getMessage());
                $errors++;
            }
        }

        $bar->finish();
        $this->newLine(2);

        // Summary
        $this->info('🎉 TTS Generation Complete!');
        $this->info("📊 Summary:");
        $this->info("  • Generated: {$generated} new audio files");
        $this->info("  • Skipped: {$skipped} (already existed)");
        $this->info("  • Errors: {$errors}");

        if ($generated > 0) {
            $this->info("✅ Successfully generated TTS for {$generated} vocabulary words!");
        }

        return 0;
    }
}
