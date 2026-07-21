<?php

namespace App\Console\Commands;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\Option;
use App\Models\Vocabulary;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

class SummerPracticePalCoverage extends Command
{
    protected $signature = 'talma:summer-practice-pal-coverage
                            {--cefr= : Limit to one CEFR level (Pre-A1, A1, A2, or B1)}
                            {--incomplete : List lessons missing full vocabulary enrichment}';

    protected $description = 'Report translation, image, and TTS coverage for Summer Practice Pal lessons';

    /** @var array<string, string> */
    private const COURSE_SLUGS = [
        'Pre-A1' => 'summer-practice-pal-pre-a1',
        'A1' => 'summer-practice-pal-a1',
        'A2' => 'summer-practice-pal-a2',
        'B1' => 'summer-practice-pal-b1',
    ];

    public function handle(): int
    {
        $slugs = $this->courseSlugs();
        if ($slugs === []) {
            $this->error('Unknown --cefr value. Use Pre-A1, A1, A2, or B1.');

            return self::FAILURE;
        }

        $courses = Course::query()
            ->whereIn('slug', $slugs)
            ->orderBy('sort_order')
            ->get();

        if ($courses->isEmpty()) {
            $this->warn('No Summer Practice Pal courses found. Run talma:import-summer-practice-pal first.');

            return self::SUCCESS;
        }

        $lessonIds = Lesson::query()
            ->whereIn('course_id', $courses->pluck('id'))
            ->where('is_active', true)
            ->pluck('id');

        $vocabByLesson = Vocabulary::query()
            ->whereIn('lesson_id', $lessonIds)
            ->where('is_active', true)
            ->get()
            ->groupBy('lesson_id');

        $optionsByLesson = Option::query()
            ->whereHas('prompt', fn ($q) => $q->whereIn('lesson_id', $lessonIds)->where('is_active', true))
            ->with('prompt:id,lesson_id')
            ->get()
            ->groupBy(fn (Option $option) => $option->prompt->lesson_id);

        $this->info('Summer Practice Pal enrichment coverage');
        $this->info('=====================================');
        $this->newLine();
        $this->line('A lesson counts as <info>fully enriched</info> when every active vocabulary word has the field.');
        $this->line('Prompt TTS is tracked separately on Sentence Completion options (word + sentence audio).');
        $this->newLine();

        $totals = $this->emptyTotals();
        $incomplete = [];

        foreach ($courses as $course) {
            $courseLessons = $course->lessons()->where('is_active', true)->orderBy('sort_order')->get();
            $courseStats = $this->emptyTotals();
            $courseStats['lessons'] = $courseLessons->count();

            foreach ($courseLessons as $lesson) {
                $lessonStats = $this->lessonStats(
                    $vocabByLesson->get($lesson->id, collect()),
                    $optionsByLesson->get($lesson->id, collect()),
                );

                $this->accumulate($courseStats, $lessonStats);
                $this->accumulate($totals, $lessonStats);

                if ($this->option('incomplete') && !$lessonStats['fully_enriched']) {
                    $incomplete[] = [
                        'course' => $course->title,
                        'lesson' => $lesson->title,
                        'vocab' => $lessonStats['vocab_count'],
                        'trans' => $lessonStats['translated'],
                        'images' => $lessonStats['with_image'],
                        'vocab_tts' => $lessonStats['with_vocab_tts'],
                        'prompt_tts' => $lessonStats['with_prompt_option_tts'],
                    ];
                }
            }

            $this->printCourseSummary($course->title, $courseStats);
        }

        if ($courses->count() > 1) {
            $this->printCourseSummary('All courses', $totals);
        }

        if ($this->option('incomplete') && $incomplete !== []) {
            $this->newLine();
            $this->info('Lessons not fully enriched (vocab translations + images + vocab TTS):');
            $this->table(
                ['Course', 'Lesson', 'Vocab', 'Translated', 'Images', 'Vocab TTS', 'Prompt opt TTS'],
                $incomplete,
            );
        }

        return self::SUCCESS;
    }

    /**
     * @return list<string>
     */
    private function courseSlugs(): array
    {
        $cefr = $this->option('cefr');
        if ($cefr === null || $cefr === '') {
            return array_values(self::COURSE_SLUGS);
        }

        $cefr = (string) $cefr;
        if (!isset(self::COURSE_SLUGS[$cefr])) {
            return [];
        }

        return [self::COURSE_SLUGS[$cefr]];
    }

    /**
     * @return array<string, int|bool>
     */
    private function lessonStats(Collection $vocab, Collection $options): array
    {
        $vocabCount = $vocab->count();
        $translated = $vocab->filter(
            fn (Vocabulary $v) => filled($v->hebrew_translation) && filled($v->arabic_translation)
        )->count();
        $withImage = $vocab->filter(fn (Vocabulary $v) => filled($v->image_path))->count();
        $withVocabTts = $vocab->filter(fn (Vocabulary $v) => $v->hasAudioFile())->count();

        $optionCount = $options->count();
        $withPromptOptionTts = $options->filter(
            fn (Option $o) => filled($o->word_audio_path) && filled($o->sentence_audio_path)
        )->count();

        $fullTrans = $vocabCount > 0 && $translated === $vocabCount;
        $fullImages = $vocabCount > 0 && $withImage === $vocabCount;
        $fullVocabTts = $vocabCount > 0 && $withVocabTts === $vocabCount;
        $fullPromptTts = $optionCount > 0 && $withPromptOptionTts === $optionCount;

        return [
            'lessons' => 1,
            'vocab_count' => $vocabCount,
            'vocab_items' => $vocabCount,
            'translated' => $translated,
            'with_image' => $withImage,
            'with_vocab_tts' => $withVocabTts,
            'prompt_options' => $optionCount,
            'with_prompt_option_tts' => $withPromptOptionTts,
            'full_trans' => $fullTrans ? 1 : 0,
            'full_images' => $fullImages ? 1 : 0,
            'full_vocab_tts' => $fullVocabTts ? 1 : 0,
            'full_prompt_tts' => $fullPromptTts ? 1 : 0,
            'any_trans' => $translated > 0 ? 1 : 0,
            'any_images' => $withImage > 0 ? 1 : 0,
            'any_vocab_tts' => $withVocabTts > 0 ? 1 : 0,
            'any_prompt_tts' => $withPromptOptionTts > 0 ? 1 : 0,
            'fully_enriched' => $fullTrans && $fullImages && $fullVocabTts,
            'no_vocab' => $vocabCount === 0 ? 1 : 0,
        ];
    }

    /**
     * @return array<string, int>
     */
    private function emptyTotals(): array
    {
        return [
            'lessons' => 0,
            'vocab_items' => 0,
            'translated' => 0,
            'with_image' => 0,
            'with_vocab_tts' => 0,
            'prompt_options' => 0,
            'with_prompt_option_tts' => 0,
            'full_trans' => 0,
            'full_images' => 0,
            'full_vocab_tts' => 0,
            'full_prompt_tts' => 0,
            'any_trans' => 0,
            'any_images' => 0,
            'any_vocab_tts' => 0,
            'any_prompt_tts' => 0,
            'no_vocab' => 0,
        ];
    }

    /**
     * @param array<string, int|bool> $target
     * @param array<string, int|bool> $source
     */
    private function accumulate(array &$target, array $source): void
    {
        foreach ($source as $key => $value) {
            if ($key === 'fully_enriched' || $key === 'lessons') {
                continue;
            }
            if (!isset($target[$key])) {
                $target[$key] = 0;
            }
            $target[$key] += (int) $value;
        }
    }

    /**
     * @param array<string, int> $stats
     */
    private function printCourseSummary(string $title, array $stats): void
    {
        $lessons = max(1, $stats['lessons']);

        $this->info($title);
        $this->line("  Lessons: {$stats['lessons']}");
        $this->line("  Vocabulary items: {$stats['vocab_items']}");
        $this->line("  Prompt options: {$stats['prompt_options']}");
        $this->newLine();

        $this->table(
            ['Metric', 'Items with data', 'Lessons (all words/options)', 'Lessons (any)'],
            [
                [
                    'Translations (Hebrew + Arabic)',
                    "{$stats['translated']}/{$stats['vocab_items']}",
                    "{$stats['full_trans']}/{$lessons}",
                    "{$stats['any_trans']}/{$lessons}",
                ],
                [
                    'Images',
                    "{$stats['with_image']}/{$stats['vocab_items']}",
                    "{$stats['full_images']}/{$lessons}",
                    "{$stats['any_images']}/{$lessons}",
                ],
                [
                    'Vocabulary TTS',
                    "{$stats['with_vocab_tts']}/{$stats['vocab_items']}",
                    "{$stats['full_vocab_tts']}/{$lessons}",
                    "{$stats['any_vocab_tts']}/{$lessons}",
                ],
                [
                    'Prompt option TTS',
                    "{$stats['with_prompt_option_tts']}/{$stats['prompt_options']}",
                    "{$stats['full_prompt_tts']}/{$lessons}",
                    "{$stats['any_prompt_tts']}/{$lessons}",
                ],
            ],
        );

        $fullyEnriched = min(
            $stats['full_trans'],
            $stats['full_images'],
            $stats['full_vocab_tts'],
        );
        $this->line("  Lessons with full vocab enrichment (trans + images + TTS): {$fullyEnriched}/{$lessons}");
        if ($stats['no_vocab'] > 0) {
            $this->warn("  {$stats['no_vocab']} lesson(s) have no active vocabulary.");
        }
        $this->newLine();
    }
}
