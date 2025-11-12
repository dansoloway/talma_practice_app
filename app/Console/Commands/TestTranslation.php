<?php

namespace App\Console\Commands;

use App\Services\Translation\OpenAiTranslator;
use Illuminate\Console\Command;

class TestTranslation extends Command
{
    protected $signature = 'test:translation {word=hello : The English word to translate}';

    protected $description = 'Test OpenAI translation service with a single word';

    public function handle(OpenAiTranslator $translator)
    {
        $word = $this->argument('word');

        if (!$translator->enabled()) {
            $this->error('OpenAI API key is not configured. Please set OPENAI_API_KEY in your .env file.');
            return 1;
        }

        $this->info("Testing translation for: {$word}");
        $this->info("This may take a few seconds...");
        
        $translations = $translator->translate($word, true, true);

        if (empty($translations['hebrew']) && empty($translations['arabic'])) {
            $this->error("❌ Translation failed. Check logs for details.");
            $this->info("Check logs: tail -f storage/logs/laravel.log");
            return 1;
        }

        $this->info("");
        $this->info("✅ Translation successful!");
        $this->info("");
        $this->table(
            ['Language', 'Translation'],
            [
                ['English', $word],
                ['Hebrew', $translations['hebrew'] ?? 'N/A'],
                ['Arabic', $translations['arabic'] ?? 'N/A'],
            ]
        );

        return 0;
    }
}

