<?php

namespace App\Console\Commands;

use App\Services\ImageGeneration\FreepikImageGenerator;
use Illuminate\Console\Command;

class TestFreepikImage extends Command
{
    protected $signature = 'test:freepik-image {word=book : The vocabulary word to test}';

    protected $description = 'Test Freepik image search and download with a single word';

    public function handle()
    {
        $word = $this->argument('word');
        $generator = app(FreepikImageGenerator::class);

        if (!$generator->enabled()) {
            $this->error('Freepik API key is not configured. Please set FREEPIK_API_KEY in your .env file.');
            return 1;
        }

        $this->info("Testing Freepik image search for: {$word}");
        $this->info("API Key configured: " . substr(config('services.freepik.api_key'), 0, 10) . '...');
        $this->info("Searching Freepik API...");
        
        // Check logs for detailed info
        $logFile = storage_path('logs/laravel.log');
        $logSizeBefore = file_exists($logFile) ? filesize($logFile) : 0;
        
        $imagePath = $generator->generateVocabularyImage($word);

        // Check what was logged
        if (file_exists($logFile)) {
            $logSizeAfter = filesize($logFile);
            if ($logSizeAfter > $logSizeBefore) {
                $this->info("Check logs for details: tail -20 storage/logs/laravel.log");
            }
        }

        if ($imagePath) {
            $this->info("✅ Success! Image saved to: {$imagePath}");
            $this->info("URL: " . asset('storage/' . $imagePath));
            $this->info("Full path: " . storage_path('app/public/' . $imagePath));
            return 0;
        } else {
            $this->warn("⚠️  No image found for '{$word}' on Freepik.");
            $this->info("Check logs for API response details: tail -20 storage/logs/laravel.log");
            $this->info("You can upload an image manually for this word.");
            return 0; // This is not an error - just means the image doesn't exist
        }
    }
}

