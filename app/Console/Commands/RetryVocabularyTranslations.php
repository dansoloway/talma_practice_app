<?php

namespace App\Console\Commands;

use App\Models\Vocabulary;
use App\Services\Translation\OpenAiTranslator;
use Illuminate\Console\Command;

class RetryVocabularyTranslations extends Command
{
    protected $signature = 'vocabulary:retry-translations 
                            {--lesson-id= : Specific lesson ID to retry translations for}
                            {--all : Retry all vocabulary items missing translations}';

    protected $description = 'Retry OpenAI translations for vocabulary items missing Hebrew or Arabic translations';

    protected $translator;

    public function __construct(OpenAiTranslator $translator)
    {
        parent::__construct();
        $this->translator = $translator;
    }

    public function handle()
    {
        if (!$this->translator->enabled()) {
            $this->error('OpenAI API key is not configured. Please set OPENAI_API_KEY in your .env file.');
            return 1;
        }

        $query = Vocabulary::where(function ($q) {
            $q->whereNull('hebrew_translation')
              ->orWhereNull('arabic_translation');
        });

        if ($this->option('lesson-id')) {
            $query->where('lesson_id', $this->option('lesson-id'));
        }

        $vocabulary = $query->get();

        if ($vocabulary->isEmpty()) {
            $this->info('No vocabulary items found missing translations.');
            return 0;
        }

        $this->info("Found {$vocabulary->count()} vocabulary items missing translations.");
        $this->newLine();

        $successCount = 0;
        $failCount = 0;

        foreach ($vocabulary as $vocab) {
            $this->line("Translating: {$vocab->english_word}...");

            $needsHebrew = empty($vocab->hebrew_translation);
            $needsArabic = empty($vocab->arabic_translation);

            $translations = $this->translator->translate(
                $vocab->english_word,
                $needsHebrew,
                $needsArabic
            );

            $updated = false;
            if ($needsHebrew && !empty($translations['hebrew'])) {
                $vocab->hebrew_translation = $translations['hebrew'];
                $updated = true;
            }
            if ($needsArabic && !empty($translations['arabic'])) {
                $vocab->arabic_translation = $translations['arabic'];
                $updated = true;
            }

            if ($updated) {
                $vocab->save();
                $this->info("  ✓ Successfully translated {$vocab->english_word}");
                $successCount++;
            } else {
                $this->warn("  ✗ Failed to translate {$vocab->english_word}");
                $failCount++;
            }
        }

        $this->newLine();
        $this->info("Completed: {$successCount} succeeded, {$failCount} failed.");

        return 0;
    }
}

