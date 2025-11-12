<?php

namespace App\Console\Commands;

use App\Services\ImageGeneration\LeonardoImageGenerator;
use Illuminate\Console\Command;

class TestLeonardoImage extends Command
{
    protected $signature = 'test:leonardo-image {word=sand : The vocabulary word to test}';

    protected $description = 'Test Leonardo.ai image generation with a single word';

    public function handle()
    {
        $word = $this->argument('word');
        $generator = app(LeonardoImageGenerator::class);

        if (!$generator->enabled()) {
            $this->error('Leonardo.ai API key is not configured. Please set LEONARDO_API_KEY in your .env file.');
            return 1;
        }

        $this->info("Testing Leonardo.ai image generation for: {$word}");
        $this->info("This may take 30-60 seconds...");
        
        $imagePath = $generator->generateVocabularyImage($word);

        if ($imagePath) {
            $this->info("✅ Success! Image saved to: {$imagePath}");
            $this->info("URL: " . asset('storage/' . $imagePath));
            return 0;
        } else {
            $this->error("❌ Failed to generate image. Check logs for details.");
            return 1;
        }
    }
}

