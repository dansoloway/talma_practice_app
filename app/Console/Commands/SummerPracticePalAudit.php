<?php

namespace App\Console\Commands;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\Option;
use App\Models\Prompt;
use App\Models\Vocabulary;
use App\Services\Import\SummerVocabAssetArchiver;
use App\Services\Import\VocabularyWordValidator;
use Illuminate\Console\Command;

class SummerPracticePalAudit extends Command
{
    protected $signature = 'talma:summer-practice-pal-audit
                            {--cefr= : Limit to one CEFR level (Pre-A1, A1, A2, or B1)}
                            {--list-vocab : List every vocabulary word grouped by lesson}';

    protected $description = 'Audit Summer Practice Pal lessons for vocab count, prompts, duplicate slugs, and invalid words';

    public function handle(): int
    {
        $slugs = $this->courseSlugs();
        if ($slugs === null) {
            $this->error('Unknown --cefr value. Use Pre-A1, A1, A2, or B1.');

            return self::FAILURE;
        }

        $courses = Course::query()->whereIn('slug', $slugs)->orderBy('sort_order')->get();
        if ($courses->isEmpty()) {
            $this->warn('No Summer Practice Pal courses found.');

            return self::SUCCESS;
        }

        $this->info('Summer Practice Pal audit');
        $this->info('========================');
        $this->newLine();

        $validator = app(VocabularyWordValidator::class);
        $wordCountIssues = [];
        $emptyLessons = [];
        $duplicateSlugs = [];
        $invalidVocab = [];
        $vocabByLesson = [];

        foreach ($courses as $course) {
            $lessons = $course->lessons()->orderBy('sort_order')->get();
            $slugCounts = $lessons->groupBy('slug')->filter(fn ($group) => $group->count() > 1);

            foreach ($slugCounts as $slug => $group) {
                $duplicateSlugs[] = [
                    'course' => $course->title,
                    'slug' => $slug,
                    'count' => $group->count(),
                ];
            }

            foreach ($lessons as $lesson) {
                $vocabItems = Vocabulary::query()
                    ->where('lesson_id', $lesson->id)
                    ->where('is_active', true)
                    ->orderBy('sort_order')
                    ->get(['english_word']);
                $vocabCount = $vocabItems->count();
                $words = $vocabItems->pluck('english_word')->all();

                if ($this->option('list-vocab')) {
                    $vocabByLesson[] = [
                        'course' => $course->title,
                        'lesson' => $lesson->title,
                        'count' => $vocabCount,
                        'words' => $words,
                    ];
                }

                foreach ($words as $word) {
                    $validation = $validator->validate($word);
                    if (!$validation['valid']) {
                        $invalidVocab[] = [
                            'course' => $course->title,
                            'lesson' => $lesson->title,
                            'word' => $word,
                            'reason' => $validation['reason'] ?? 'invalid',
                        ];
                    }
                }
                $promptCount = Prompt::query()
                    ->where('lesson_id', $lesson->id)
                    ->where('is_active', true)
                    ->count();
                $optionCount = Option::query()
                    ->whereHas('prompt', fn ($q) => $q->where('lesson_id', $lesson->id)->where('is_active', true))
                    ->count();

                $wordValidation = app(VocabularyWordValidator::class)->validateLessonWordCount($vocabCount);
                if (!$wordValidation['valid']) {
                    $wordCountIssues[] = [
                        'course' => $course->title,
                        'lesson' => $lesson->title,
                        'slug' => $lesson->slug,
                        'vocab' => $vocabCount,
                        'prompts' => $promptCount,
                        'reason' => $wordValidation['reason'],
                    ];
                }

                if ($vocabCount === 0 || $promptCount === 0) {
                    $emptyLessons[] = [
                        'course' => $course->title,
                        'lesson' => $lesson->title,
                        'vocab' => $vocabCount,
                        'prompts' => $promptCount,
                        'options' => $optionCount,
                    ];
                }
            }
        }

        if ($this->option('list-vocab')) {
            $this->info('Vocabulary by lesson');
            $this->info('==================');
            $this->newLine();
            foreach ($vocabByLesson as $row) {
                $this->line("<info>{$row['lesson']}</info> ({$row['count']} words)");
                $this->line('  ' . ($row['words'] === [] ? '(none)' : implode(', ', $row['words'])));
                $this->newLine();
            }
        }

        $this->line('Invalid vocabulary entries (sentences, phrases, activity titles): ' . count($invalidVocab));
        if ($invalidVocab !== []) {
            $this->table(
                ['Course', 'Lesson', 'Word', 'Issue'],
                collect($invalidVocab)->map(fn ($row) => [
                    $row['course'],
                    $row['lesson'],
                    $row['word'],
                    $row['reason'],
                ])->all()
            );
            $this->newLine();
        }

        $this->line('Lessons outside 5–10 vocabulary words: ' . count($wordCountIssues));
        if ($wordCountIssues !== []) {
            $this->table(
                ['Course', 'Lesson', 'Vocab', 'Prompts', 'Issue'],
                collect($wordCountIssues)->map(fn ($row) => [
                    $row['course'],
                    $row['lesson'],
                    $row['vocab'],
                    $row['prompts'],
                    $row['reason'],
                ])->all()
            );
            $this->newLine();
        }

        $missingVocab = collect($emptyLessons)->where('vocab', 0)->count();
        $missingPrompts = collect($emptyLessons)->where('prompts', 0)->count();
        $this->line("Lessons missing vocab: {$missingVocab}");
        $this->line("Lessons missing prompts: {$missingPrompts}");

        if ($emptyLessons !== []) {
            $this->table(
                ['Course', 'Lesson', 'Vocab', 'Prompts', 'Options'],
                collect($emptyLessons)->map(fn ($row) => [
                    $row['course'],
                    $row['lesson'],
                    $row['vocab'],
                    $row['prompts'],
                    $row['options'],
                ])->all()
            );
            $this->newLine();
        }

        $this->line('Duplicate lesson slugs: ' . count($duplicateSlugs));
        if ($duplicateSlugs !== []) {
            $this->table(
                ['Course', 'Slug', 'Count'],
                collect($duplicateSlugs)->map(fn ($row) => [
                    $row['course'],
                    $row['slug'],
                    $row['count'],
                ])->all()
            );
        }

        return self::SUCCESS;
    }

    /**
     * @return list<string>|null
     */
    private function courseSlugs(): ?array
    {
        $cefr = $this->option('cefr');
        if ($cefr === null || $cefr === '') {
            return array_values(SummerVocabAssetArchiver::COURSE_SLUGS);
        }

        $normalized = \App\Services\Import\SummerImportOptions::normalizeCefr((string) $cefr);
        if (!isset(SummerVocabAssetArchiver::COURSE_SLUGS[$normalized])) {
            return null;
        }

        return [SummerVocabAssetArchiver::COURSE_SLUGS[$normalized]];
    }
}
