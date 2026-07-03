<?php

namespace App\Console\Commands;

use App\Models\Lesson;
use App\Models\Option;
use App\Services\Tts\PromptOptionTtsService;
use Illuminate\Console\Command;

class GeneratePromptOptionTts extends Command
{
    protected $signature = 'tts:generate-prompt-audio
                            {--lesson= : Generate TTS for a specific lesson ID}
                            {--words : Generate word audio only}
                            {--sentences : Generate sentence audio only}
                            {--force : Regenerate even if audio already exists}';

    protected $description = 'Generate ElevenLabs TTS for prompt option words and/or example sentences';

    public function handle(PromptOptionTtsService $ttsService): int
    {
        if (! $ttsService->enabled()) {
            $this->error('ELEVENLABS_API_KEY not found in .env');

            return self::FAILURE;
        }

        $generateWords = $this->option('words') || ! $this->option('sentences');
        $generateSentences = $this->option('sentences') || ! $this->option('words');

        $query = Option::query()
            ->with('prompt')
            ->where('is_active', true)
            ->whereHas('prompt', function ($q) {
                $q->where('is_active', true);

                if ($lessonId = $this->option('lesson')) {
                    $q->where('lesson_id', $lessonId);
                }
            })
            ->orderBy('id');

        $options = $query->get();

        if ($options->isEmpty()) {
            $this->warn('No prompt options found.');

            return self::SUCCESS;
        }

        if ($lessonId = $this->option('lesson')) {
            $lesson = Lesson::find($lessonId);
            $lessonLabel = $lesson ? "{$lesson->id} ({$lesson->title})" : (string) $lessonId;
            $this->info("Generating prompt TTS for lesson {$lessonLabel}");
        } else {
            $this->info('Generating prompt TTS for all lessons');
        }

        $this->info("Processing {$options->count()} option(s)");
        $this->newLine();

        $generatedWords = 0;
        $generatedSentences = 0;
        $skippedWords = 0;
        $skippedSentences = 0;
        $errors = 0;

        $bar = $this->output->createProgressBar($options->count());
        $bar->start();

        foreach ($options as $option) {
            $bar->advance();

            if ($generateWords) {
                if (! $this->option('force') && $this->audioExists($option->word_audio_path)) {
                    $skippedWords++;
                } elseif ($ttsService->generateWordForOption($option->fresh())) {
                    $generatedWords++;
                } else {
                    $errors++;
                    $this->newLine();
                    $this->error("Word TTS failed for option {$option->id} ({$option->label})");
                }

                usleep(200000);
            }

            if ($generateSentences) {
                $option->refresh();

                if (! $this->option('force') && $this->audioExists($option->sentence_audio_path)) {
                    $skippedSentences++;
                } elseif ($ttsService->generateSentenceForOption($option->fresh())) {
                    $generatedSentences++;
                } else {
                    $errors++;
                    $this->newLine();
                    $this->error("Sentence TTS failed for option {$option->id} ({$option->label})");
                }

                usleep(200000);
            }
        }

        $bar->finish();
        $this->newLine(2);

        if ($generateWords) {
            $this->info("Word audio: {$generatedWords} generated, {$skippedWords} skipped");
        }

        if ($generateSentences) {
            $this->info("Sentence audio: {$generatedSentences} generated, {$skippedSentences} skipped");
        }

        if ($errors > 0) {
            $this->warn("Errors: {$errors}. Check storage/logs/laravel.log and storage/logs/tts_generation.log");

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    private function audioExists(?string $path): bool
    {
        if (! filled($path)) {
            return false;
        }

        return file_exists(public_path(ltrim($path, '/')));
    }
}
