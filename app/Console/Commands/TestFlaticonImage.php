<?php

namespace App\Console\Commands;

use App\Services\ImageGeneration\FlaticonImageGenerator;
use Illuminate\Console\Command;

class TestFlaticonImage extends Command
{
    protected $signature = 'test:flaticon-image {word=sand : The vocabulary word to test}';

    protected $description = 'Test Flaticon icon search and download with a single word';

    public function handle()
    {
        $word = $this->argument('word');
        $generator = app(FlaticonImageGenerator::class);

        if (!config('services.flaticon.api_key')) {
            $this->error('Flaticon API key is not configured. Please set FLATICON_API_KEY in your .env file.');
            return 1;
        }

        if (!$generator->enabled()) {
            $this->error('FLATICON_API_KEY is set but invalid for Flaticon (it may be a Freepik key).');
            $this->line('Get a separate key at https://api.flaticon.com — it is not the same as FREEPIK_API_KEY.');
            return 1;
        }

        $this->info("Testing Flaticon icon search for: {$word}");
        $this->info("Searching Flaticon...");
        
        $imagePath = $generator->generateVocabularyImage($word);

        if ($imagePath) {
            $this->info("✅ Success! Icon saved to: {$imagePath}");
            $this->info("URL: " . asset('storage/' . $imagePath));
            $this->info("Full path: " . storage_path('app/public/' . $imagePath));
            return 0;
        } else {
            $this->warn("⚠️  No icon found for '{$word}' on Flaticon.");
            $this->info("You can upload an image manually for this word.");
            return 0; // This is not an error - just means the icon doesn't exist
        }
    }
}

