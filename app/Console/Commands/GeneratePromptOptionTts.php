<?php

namespace App\Console\Commands;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\Option;
use App\Services\Tts\PromptOptionTtsService;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class GeneratePromptOptionTts extends Command
{
    /** @var array<string, string> */
    private const SUMMER_COURSE_SLUGS = [
        'Pre-A1' => 'summer-practice-pal-pre-a1',
        'A1' => 'summer-practice-pal-a1',
        'A2' => 'summer-practice-pal-a2',
        'B1' => 'summer-practice-pal-b1',
    ];

    protected $signature = 'tts:generate-prompt-audio
                            {--lesson= : Generate TTS for a specific lesson ID}
                            {--course= : Limit to a course ID or slug}
                            {--summer : Limit to Summer Practice Pal courses}
                            {--cefr= : With --summer, limit to one CEFR level (Pre-A1, A1, A2, or B1)}
                            {--words : Generate word audio only}
                            {--sentences : Generate sentence audio only}
                            {--force : Regenerate even if audio already exists}
                            {--dry-run : Show how many options need generation without calling ElevenLabs}
                            {--yes : Skip confirmation prompt}';

    protected $description = 'Generate ElevenLabs TTS for prompt option words and/or example sentences';

    public function handle(PromptOptionTtsService $ttsService): int
    {
        if (! $ttsService->enabled()) {
            $this->error('ELEVENLABS_API_KEY not found in .env');

            return self::FAILURE;
        }

        $generateWords = $this->option('words') || ! $this->option('sentences');
        $generateSentences = $this->option('sentences') || ! $this->option('words');
        $force = (bool) $this->option('force');

        if ($this->option('summer') && $this->option('cefr') && $this->summerCourseSlugs() === []) {
            $this->error('Unknown --cefr value. Use Pre-A1, A1, A2, or B1.');

            return self::FAILURE;
        }

        $options = $this->optionsQuery()->with(['prompt.lesson', 'prompt.lesson.course'])->orderBy('id')->get();

        if ($options->isEmpty()) {
            $this->warn('No active prompt options found.');

            return self::SUCCESS;
        }

        $pendingWords = $generateWords
            ? $options->filter(fn (Option $option) => $force || ! $this->wordAudioExists($option))->count()
            : 0;
        $pendingSentences = $generateSentences
            ? $options->filter(fn (Option $option) => $force || ! $this->sentenceAudioExists($option))->count()
            : 0;

        $this->printScopeSummary($options);
        $this->newLine();

        if ($generateSentences) {
            $this->info("Sentence audio pending: {$pendingSentences} of {$options->count()} option(s)");
        }

        if ($generateWords) {
            $this->info("Word audio pending: {$pendingWords} of {$options->count()} option(s)");
        }

        if ($pendingWords === 0 && $pendingSentences === 0) {
            $this->info('Nothing to generate.');

            return self::SUCCESS;
        }

        $this->printLessonBreakdown($options, $generateWords, $generateSentences, $force);
        $this->newLine();

        if ($this->option('dry-run')) {
            $this->comment('Dry run only. Re-run without --dry-run to generate audio.');

            return self::SUCCESS;
        }

        if (! $this->option('yes') && ! $this->confirm('Start ElevenLabs generation?', true)) {
            return self::SUCCESS;
        }

        $generatedWords = 0;
        $generatedSentences = 0;
        $skippedWords = 0;
        $skippedSentences = 0;
        $errors = 0;

        $bar = $this->output->createProgressBar($options->count());
        $bar->setFormat(' %current%/%max% [%bar%] %percent:3s%% — %message%');
        $bar->setMessage('starting');
        $bar->start();

        foreach ($options as $option) {
            $lesson = $option->prompt?->lesson;
            $bar->setMessage($lesson ? "lesson {$lesson->id}: {$lesson->title}" : "option {$option->id}");

            if ($generateWords) {
                if (! $force && $this->wordAudioExists($option)) {
                    $skippedWords++;
                } elseif ($ttsService->generateWordForOption($option->fresh())) {
                    $generatedWords++;
                } else {
                    $errors++;
                    $bar->clear();
                    $this->error("Word TTS failed for option {$option->id} ({$option->label})");
                    $bar->display();
                }

                usleep(200000);
            }

            if ($generateSentences) {
                $option->refresh();

                if (! $force && $this->sentenceAudioExists($option)) {
                    $skippedSentences++;
                } elseif ($ttsService->generateSentenceForOption($option->fresh())) {
                    $generatedSentences++;
                } else {
                    $errors++;
                    $bar->clear();
                    $this->error("Sentence TTS failed for option {$option->id} ({$option->label})");
                    $bar->display();
                }

                usleep(200000);
            }

            $bar->advance();
        }

        $bar->setMessage('done');
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

    private function optionsQuery(): Builder
    {
        return Option::query()
            ->where('is_active', true)
            ->whereHas('prompt', function ($q) {
                $q->where('is_active', true)
                    ->whereHas('lesson', function ($lessonQuery) {
                        $lessonQuery->where('is_active', true)
                            ->whereNull('archived_at');

                        if ($lessonId = $this->option('lesson')) {
                            $lessonQuery->where('id', $lessonId);
                        }

                        if ($this->option('summer')) {
                            $slugs = $this->summerCourseSlugs();
                            $lessonQuery->whereHas('course', fn ($q) => $q->whereIn('slug', $slugs));
                        } elseif ($course = $this->option('course')) {
                            $lessonQuery->where(function ($courseQuery) use ($course) {
                                $courseQuery->where('course_id', $course)
                                    ->orWhereHas('course', fn ($q) => $q->where('slug', $course));
                            });
                        }
                    });

                if ($lessonId = $this->option('lesson')) {
                    $q->where('lesson_id', $lessonId);
                }
            });
    }

    private function printScopeSummary(Collection $options): void
    {
        if ($lessonId = $this->option('lesson')) {
            $lesson = Lesson::find($lessonId);
            $lessonLabel = $lesson ? "{$lesson->id} ({$lesson->title})" : (string) $lessonId;
            $this->info("Scope: lesson {$lessonLabel}");

            return;
        }

        if ($this->option('summer')) {
            $slugs = $this->summerCourseSlugs();
            $this->info('Scope: Summer Practice Pal ('.implode(', ', $slugs).')');
        } elseif ($course = $this->option('course')) {
            $courseModel = Course::query()
                ->where('id', $course)
                ->orWhere('slug', $course)
                ->first();
            $courseLabel = $courseModel ? "{$courseModel->title} ({$courseModel->slug})" : (string) $course;
            $this->info("Scope: course {$courseLabel}");
        } else {
            $this->info('Scope: all active, non-archived lessons');
        }

        $lessonCount = $options
            ->pluck('prompt.lesson_id')
            ->filter()
            ->unique()
            ->count();

        $this->info("Lessons in scope: {$lessonCount}");
        $this->info("Options in scope: {$options->count()}");
    }

    private function printLessonBreakdown(
        Collection $options,
        bool $generateWords,
        bool $generateSentences,
        bool $force
    ): void {
        $rows = $options
            ->groupBy(fn (Option $option) => $option->prompt?->lesson_id)
            ->sortKeys()
            ->map(function (Collection $lessonOptions, $lessonId) use ($generateWords, $generateSentences, $force) {
                $lesson = $lessonOptions->first()->prompt?->lesson;
                $pendingWords = $generateWords
                    ? $lessonOptions->filter(fn (Option $option) => $force || ! $this->wordAudioExists($option))->count()
                    : 0;
                $pendingSentences = $generateSentences
                    ? $lessonOptions->filter(fn (Option $option) => $force || ! $this->sentenceAudioExists($option))->count()
                    : 0;

                if ($pendingWords === 0 && $pendingSentences === 0) {
                    return null;
                }

                return [
                    'lesson_id' => $lessonId,
                    'title' => $lesson?->title ?? 'Unknown',
                    'pending_sentences' => $pendingSentences,
                    'pending_words' => $pendingWords,
                ];
            })
            ->filter()
            ->values();

        if ($rows->isEmpty()) {
            return;
        }

        $this->table(
            ['Lesson', 'Title', 'Sentences pending', 'Words pending'],
            $rows->map(fn (array $row) => [
                $row['lesson_id'],
                $row['title'],
                $row['pending_sentences'],
                $row['pending_words'],
            ])->all()
        );
    }

    private function wordAudioExists(Option $option): bool
    {
        return $this->audioExists($option->word_audio_path);
    }

    private function sentenceAudioExists(Option $option): bool
    {
        return $this->audioExists($option->sentence_audio_path);
    }

    private function audioExists(?string $path): bool
    {
        if (! filled($path)) {
            return false;
        }

        return file_exists(public_path(ltrim($path, '/')));
    }

    /**
     * @return list<string>
     */
    private function summerCourseSlugs(): array
    {
        $cefr = $this->option('cefr');

        if ($cefr === null) {
            return array_values(self::SUMMER_COURSE_SLUGS);
        }

        $normalized = match (strtoupper((string) $cefr)) {
            'PRE-A1', 'PREA1' => 'Pre-A1',
            'A1' => 'A1',
            'A2' => 'A2',
            'B1' => 'B1',
            default => null,
        };

        if ($normalized === null || ! isset(self::SUMMER_COURSE_SLUGS[$normalized])) {
            return [];
        }

        return [self::SUMMER_COURSE_SLUGS[$normalized]];
    }
}
