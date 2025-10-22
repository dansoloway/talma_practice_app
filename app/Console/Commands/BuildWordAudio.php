<?php

namespace App\Console\Commands;

use App\Models\Option;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

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
        $apiKey = env('ELEVENLABS_API_KEY');
        
        if (!$apiKey) {
            $this->error('ELEVENLABS_API_KEY not set in .env file');
            return 1;
        }
        
        $voiceId = 'EXAVITQu4vr4xnSDxMaL'; // Rachel voice
        
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
                // Call ElevenLabs API
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
                    
                    $this->info("  ✓ Saved to: {$fullPath}");
                } else {
                    $this->error("  ✗ API Error: " . $response->status());
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


