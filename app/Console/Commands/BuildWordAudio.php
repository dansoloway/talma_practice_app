<?php

namespace App\Console\Commands;

use App\Models\Option;
use Illuminate\Console\Command;
use App\Services\Tts\ElevenLabsTtsService;

class BuildWordAudio extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tts:build-word-audio 
                            {--lesson= : Only build word audio for a specific lesson slug}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate TTS audio for individual option words using ElevenLabs';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        // Get TTS service
        $ttsService = app(ElevenLabsTtsService::class);
        
        if (!$ttsService->enabled()) {
            $this->error('ELEVENLABS_API_KEY not set in .env file');
            return 1;
        }
        
        $this->info('Building word audio files...');
        
        $query = Option::whereNull('word_audio_path')->with('prompt.lesson');
        
        if ($lessonSlug = $this->option('lesson')) {
            $query->whereHas('prompt.lesson', function ($q) use ($lessonSlug) {
                $q->where('slug', $lessonSlug);
            });
        }
        
        $options = $query->get();
        
        if ($options->isEmpty()) {
            $this->info('No options need word audio generation.');
            return 0;
        }
        
        $this->info("Generating audio for {$options->count()} word(s)...\n");
        
        $bar = $this->output->createProgressBar($options->count());
        
        foreach ($options as $option) {
            $this->newLine();
            $this->info("Generating: \"{$option->label}\"");
            
            try {
                // Use centralized TTS service
                $result = $ttsService->generateAndSaveVocabulary(
                    $option->label,
                    null, // No old path to delete
                    'EXAVITQu4vr4xnSDxMaL' // Rachel voice
                );
                
                if ($result !== null) {
                    $option->update(['word_audio_path' => $result['path']]);
                    $this->info("  ✓ Saved to: {$result['full_path']}");
                } else {
                    $this->error("  ✗ Failed to generate audio");
                }
                
                // Rate limiting
                usleep(500000); // 0.5 seconds
                
            } catch (\Exception $e) {
                $this->error("  ✗ Error: " . $e->getMessage());
            }
            
            $bar->advance();
        }
        
        $bar->finish();
        $this->newLine(2);
        $this->info('✓ Word audio generation complete!');
        
        return 0;
    }
}


