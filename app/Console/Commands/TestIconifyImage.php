<?php

namespace App\Console\Commands;

use App\Services\ImageGeneration\IconifyImageGenerator;
use Illuminate\Console\Command;

class TestIconifyImage extends Command
{
    protected $signature = 'test:iconify-image {word=pet : The vocabulary word to test}';

    protected $description = 'Test Iconify icon search and download (free, no API key)';

    public function handle()
    {
        $word = $this->argument('word');
        $generator = app(IconifyImageGenerator::class);

        if (!$generator->enabled()) {
            $this->error('Iconify is disabled. Set IMAGE_ICONIFY_ENABLED=true in .env');
            return 1;
        }

        $this->info("Testing Iconify icon search for: {$word}");

        $imagePath = $generator->generateVocabularyImage($word);

        if ($imagePath) {
            $this->info("Success! Icon saved to: {$imagePath}");
            $this->info('URL: ' . asset('storage/' . $imagePath));
            return 0;
        }

        $this->warn("No icon found for '{$word}' on Iconify.");
        return 1;
    }
}
