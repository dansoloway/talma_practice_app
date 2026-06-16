<?php

namespace App\Console\Commands;

use App\Models\Vocabulary;
use App\Services\Translation\OpenAiTranslator;
use Illuminate\Console\Command;

class RetryVocabularyTranslations extends Command
{
    protected $signature = 'vocabulary:retry-translations
                            {--lesson-id= : Specific lesson ID to retry translations for}
                            {--all : Retry all vocabulary items missing translations}
                            {--redo-arabic : Re-translate Arabic for all vocabulary using the configured OPENAI_ARABIC_VARIANT}
                            {--force : Skip confirmation when using --redo-arabic}';

    protected $description = 'Retry or re-translate OpenAI vocabulary translations (Hebrew/Arabic)';

    public function __construct(protected OpenAiTranslator $translator)
    {
        parent::__construct();
    }

    public function handle()
    {
        if (!$this->translator->enabled()) {
            $this->error('OpenAI API key is not configured. Please set OPENAI_API_KEY in your .env file.');
            return 1;
        }

        if ($this->option('redo-arabic')) {
            return $this->redoAllArabic();
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

        return $this->processVocabulary($vocabulary, missingOnly: true);
    }

    protected function redoAllArabic(): int
    {
        $variant = $this->translator->arabicVariantLabel();
        $query = Vocabulary::query()->orderBy('id');

        if ($this->option('lesson-id')) {
            $query->where('lesson_id', $this->option('lesson-id'));
        }

        $vocabulary = $query->get();

        if ($vocabulary->isEmpty()) {
            $this->info('No vocabulary items found.');
            return 0;
        }

        if (!$this->option('force') && !$this->confirm("Re-translate Arabic for {$vocabulary->count()} words using {$variant}? This will call OpenAI for each word.", true)) {
            $this->info('Cancelled.');
            return 0;
        }

        $this->info("Re-translating Arabic ({$variant}) for {$vocabulary->count()} vocabulary items...");
        $this->newLine();

        $successCount = 0;
        $failCount = 0;

        foreach ($vocabulary as $vocab) {
            $this->line("Translating: {$vocab->english_word}...");

            $this->translator->forgetCachedTranslation($vocab->english_word);

            $translations = $this->translator->translate(
                $vocab->english_word,
                needsHebrew: false,
                needsArabic: true,
                forceRefresh: true,
            );

            if (!empty($translations['arabic'])) {
                $vocab->arabic_translation = $translations['arabic'];
                $vocab->save();
                $this->info("  ✓ {$vocab->english_word} → {$translations['arabic']}");
                $successCount++;
            } else {
                $this->warn("  ✗ Failed to translate {$vocab->english_word}");
                $failCount++;
            }
        }

        $this->newLine();
        $this->info("Completed: {$successCount} succeeded, {$failCount} failed.");

        return $failCount > 0 ? 1 : 0;
    }

    protected function processVocabulary($vocabulary, bool $missingOnly): int
    {
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

        return $failCount > 0 ? 1 : 0;
    }
}
