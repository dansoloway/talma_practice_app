<?php

namespace App\Console\Commands;

use App\Models\Prompt;
use App\Models\PromptOptionAsset;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class BuildTtsAssets extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tts:build-assets 
                            {--lesson= : Only build assets for a specific lesson slug}
                            {--dry-run : Show what would be generated without actually creating files}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Pre-generate TTS audio files and database records for all prompt/option combinations';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Building TTS assets...');
        
        $query = Prompt::with(['lesson', 'options']);
        
        if ($lessonSlug = $this->option('lesson')) {
            $query->whereHas('lesson', function ($q) use ($lessonSlug) {
                $q->where('slug', $lessonSlug);
            });
        }
        
        $prompts = $query->get();
        
        if ($prompts->isEmpty()) {
            $this->error('No prompts found.');
            return 1;
        }
        
        $this->info("Found {$prompts->count()} prompt(s) to process.");
        
        $bar = $this->output->createProgressBar($prompts->count());
        $created = 0;
        $updated = 0;
        
        foreach ($prompts as $prompt) {
            $this->newLine();
            $this->info("Processing: {$prompt->lesson->title} → {$prompt->prompt_text}");
            
            foreach ($prompt->options as $option) {
                $sentence = Str::of($prompt->template)->replace('{{answer}}', $option->label);
                
                $filename = "p{$prompt->id}_o{$option->id}.mp3";
                $relativePath = "tts/lesson{$prompt->lesson_id}/{$filename}";
                $fullPath = storage_path("app/public/{$relativePath}");
                
                // Create directory if it doesn't exist
                if (!$this->option('dry-run')) {
                    $dir = dirname($fullPath);
                    if (!file_exists($dir)) {
                        mkdir($dir, 0755, true);
                    }
                }
                
                // Check if audio file needs to be generated
                $audioExists = file_exists($fullPath);
                
                if (!$audioExists) {
                    $this->warn("  ⚠ Audio file missing: {$relativePath}");
                    $this->comment("    You need to generate: \"{$sentence}\"");
                    $this->comment("    Place it at: {$fullPath}");
                }
                
                // Create or update database record
                if (!$this->option('dry-run')) {
                    $asset = PromptOptionAsset::updateOrCreate(
                        [
                            'prompt_id' => $prompt->id,
                            'option_id' => $option->id,
                        ],
                        [
                            'generated_sentence' => $sentence,
                            'audio_path' => "/storage/{$relativePath}",
                            'duration_ms' => null, // Will be populated later if needed
                        ]
                    );
                    
                    if ($asset->wasRecentlyCreated) {
                        $created++;
                        $this->info("  ✓ Created asset record: {$option->label}");
                    } else {
                        $updated++;
                        $this->info("  ↻ Updated asset record: {$option->label}");
                    }
                } else {
                    $this->info("  [DRY RUN] Would create/update: {$sentence}");
                }
            }
            
            $bar->advance();
        }
        
        $bar->finish();
        $this->newLine(2);
        
        if ($this->option('dry-run')) {
            $this->info('Dry run completed. No changes made.');
        } else {
            $this->info("✓ Sentence records complete! Created: {$created}, Updated: {$updated}");
            $this->newLine();
            
            // Also check for word audio
            $missingWordAudio = \App\Models\Option::whereNull('word_audio_path')->count();
            
            if ($missingWordAudio > 0) {
                $this->newLine();
                $this->comment("Note: {$missingWordAudio} options need word audio.");
                $this->comment('Run: php artisan tts:build-word-audio');
            }
            
            $this->newLine();
            $this->comment('Next steps:');
            $this->comment('1. Generate MP3 files using: php generate_audio.php');
            $this->comment('   (or use ElevenLabs/another TTS service manually)');
            $this->comment('2. Run: php artisan tts:verify to check all files exist');
        }
        
        return 0;
    }
}

