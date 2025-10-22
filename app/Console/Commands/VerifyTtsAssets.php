<?php

namespace App\Console\Commands;

use App\Models\PromptOptionAsset;
use Illuminate\Console\Command;

class VerifyTtsAssets extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tts:verify';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verify that all TTS audio files exist on disk';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Verifying TTS assets...');
        
        $assets = PromptOptionAsset::with(['prompt.lesson', 'option'])->get();
        
        if ($assets->isEmpty()) {
            $this->warn('No assets found. Run: php artisan tts:build-assets first.');
            return 1;
        }
        
        $missing = [];
        $found = 0;
        
        foreach ($assets as $asset) {
            // Convert /storage/ path to actual filesystem path
            $path = str_replace('/storage/', '', $asset->audio_path);
            $fullPath = storage_path("app/public/{$path}");
            
            if (!file_exists($fullPath)) {
                $missing[] = [
                    'lesson' => $asset->prompt->lesson->title,
                    'prompt' => $asset->prompt->prompt_text,
                    'option' => $asset->option->label,
                    'sentence' => $asset->generated_sentence,
                    'path' => $fullPath,
                ];
            } else {
                $found++;
            }
        }
        
        $total = $assets->count();
        
        if (empty($missing)) {
            $this->info("✓ All {$total} audio files verified!");
            return 0;
        }
        
        $this->error("✗ Missing {" . count($missing) . "} of {$total} audio files:");
        $this->newLine();
        
        foreach ($missing as $item) {
            $this->line("Lesson: {$item['lesson']}");
            $this->line("Prompt: {$item['prompt']}");
            $this->line("Option: {$item['option']}");
            $this->line("Text: \"{$item['sentence']}\"");
            $this->warn("Path: {$item['path']}");
            $this->newLine();
        }
        
        return 1;
    }
}

