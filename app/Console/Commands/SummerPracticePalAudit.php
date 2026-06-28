<?php

namespace App\Console\Commands;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\Option;
use App\Models\Prompt;
use App\Models\Vocabulary;
use App\Services\Import\SummerImportOptions;
use App\Services\Import\SummerVocabAssetArchiver;
use App\Services\Import\VocabularyWordValidator;
use App\Services\Import\XlsxReader;
use Illuminate\Console\Command;

class SummerPracticePalAudit extends Command
{
    protected $signature = 'talma:summer-practice-pal-audit
                            {--cefr= : Limit to one CEFR level (Pre-A1, A1, A2, or B1)}
                            {--list-vocab : List every vocabulary word grouped by lesson}
                            {--summary : Show only the per-course summary table}
                            {--source : Also audit vocab rows in the XLSX source spreadsheet}
                            {--strict : Exit with failure if any issue is found}';

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

        $scope = $this->option('cefr') ? (string) $this->option('cefr') : 'all levels';
        $this->info('Summer Practice Pal audit');
        $this->info('========================');
        $this->line("Scope: {$scope}");
        $this->newLine();

        $validator = app(VocabularyWordValidator::class);
        $wordCountIssues = [];
        $emptyLessons = [];
        $duplicateSlugs = [];
        $invalidVocab = [];
        $vocabByLesson = [];
        $courseSummaries = [];

        foreach ($courses as $course) {
            $courseSummary = [
                'course' => $course->title,
                'lessons' => 0,
                'invalid_vocab' => 0,
                'word_count_issues' => 0,
                'missing_vocab' => 0,
                'missing_prompts' => 0,
                'duplicate_slugs' => 0,
            ];

            $lessons = $course->lessons()->orderBy('sort_order')->get();
            $slugCounts = $lessons->groupBy('slug')->filter(fn ($group) => $group->count() > 1);

            foreach ($slugCounts as $slug => $group) {
                $courseSummary['duplicate_slugs']++;
                $duplicateSlugs[] = [
                    'course' => $course->title,
                    'slug' => $slug,
                    'count' => $group->count(),
                ];
            }

            foreach ($lessons as $lesson) {
                $courseSummary['lessons']++;

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
                        $courseSummary['invalid_vocab']++;
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

                $wordValidation = $validator->validateLessonWordCount($vocabCount);
                if (!$wordValidation['valid']) {
                    $courseSummary['word_count_issues']++;
                    $wordCountIssues[] = [
                        'course' => $course->title,
                        'lesson' => $lesson->title,
                        'slug' => $lesson->slug,
                        'vocab' => $vocabCount,
                        'prompts' => $promptCount,
                        'reason' => $wordValidation['reason'],
                    ];
                }

                if ($vocabCount === 0) {
                    $courseSummary['missing_vocab']++;
                }
                if ($promptCount === 0) {
                    $courseSummary['missing_prompts']++;
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

            $courseSummaries[] = $courseSummary;
        }

        $this->printCourseSummaryTable($courseSummaries);
        $this->newLine();

        if ($this->option('source')) {
            $this->auditXlsxSource($slugs);
            $this->newLine();
        }

        if ($this->option('summary')) {
            return $this->finish($invalidVocab, $wordCountIssues, $emptyLessons, $duplicateSlugs);
        }

        if ($this->option('list-vocab')) {
            $this->printVocabByLesson($vocabByLesson);
        }

        $this->printIssueSections($invalidVocab, $wordCountIssues, $emptyLessons, $duplicateSlugs);

        return $this->finish($invalidVocab, $wordCountIssues, $emptyLessons, $duplicateSlugs);
    }

    /**
     * @param list<array<string, int|string>> $courseSummaries
     */
    private function printCourseSummaryTable(array $courseSummaries): void
    {
        $totals = [
            'course' => 'Total',
            'lessons' => 0,
            'invalid_vocab' => 0,
            'word_count_issues' => 0,
            'missing_vocab' => 0,
            'missing_prompts' => 0,
            'duplicate_slugs' => 0,
        ];

        foreach ($courseSummaries as $row) {
            foreach ($totals as $key => $value) {
                if ($key === 'course') {
                    continue;
                }
                $totals[$key] += (int) $row[$key];
            }
        }

        $rows = collect($courseSummaries)
            ->map(fn ($row) => [
                $row['course'],
                $row['lessons'],
                $row['invalid_vocab'],
                $row['word_count_issues'],
                $row['missing_vocab'],
                $row['missing_prompts'],
                $row['duplicate_slugs'],
            ])
            ->all();

        if (count($courseSummaries) > 1) {
            $rows[] = [
                $totals['course'],
                $totals['lessons'],
                $totals['invalid_vocab'],
                $totals['word_count_issues'],
                $totals['missing_vocab'],
                $totals['missing_prompts'],
                $totals['duplicate_slugs'],
            ];
        }

        $this->info('Summary by course (database)');
        $this->table(
            ['Course', 'Lessons', 'Invalid vocab', 'Bad word count', 'Missing vocab', 'Missing prompts', 'Dup slugs'],
            $rows,
        );
    }

    /**
     * @param list<array<string, mixed>> $vocabByLesson
     */
    private function printVocabByLesson(array $vocabByLesson): void
    {
        $this->info('Vocabulary by lesson');
        $this->info('==================');
        $this->newLine();

        $currentCourse = null;
        foreach ($vocabByLesson as $row) {
            if ($currentCourse !== $row['course']) {
                $currentCourse = $row['course'];
                $this->line("<comment>{$currentCourse}</comment>");
            }

            $this->line("  <info>{$row['lesson']}</info> ({$row['count']} words)");
            $this->line('    ' . ($row['words'] === [] ? '(none)' : implode(', ', $row['words'])));
            $this->newLine();
        }
    }

    /**
     * @param list<array<string, string>> $invalidVocab
     * @param list<array<string, mixed>> $wordCountIssues
     * @param list<array<string, int|string>> $emptyLessons
     * @param list<array<string, int|string>> $duplicateSlugs
     */
    private function printIssueSections(
        array $invalidVocab,
        array $wordCountIssues,
        array $emptyLessons,
        array $duplicateSlugs,
    ): void {
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
    }

    /**
     * @param list<string> $courseSlugs
     */
    private function auditXlsxSource(array $courseSlugs): void
    {
        $xlsxPath = base_path('data/Summer Practice Pal Prompts.xlsx');
        if (!is_readable($xlsxPath)) {
            $this->warn('XLSX source not found; skipping --source audit.');

            return;
        }

        $allowedCefrs = $this->allowedCefrsFromSlugs($courseSlugs);
        $validator = app(VocabularyWordValidator::class);

        try {
            $rows = (new XlsxReader($xlsxPath))->readSheet('cleaned vocab');
        } catch (\Throwable $e) {
            $this->warn('Unable to read XLSX cleaned vocab sheet: ' . $e->getMessage());

            return;
        }

        /** @var array<string, array{cefr: string, day: int, topic: string, words: list<string>, invalid: list<array{word: string, reason: string}>}> $lessons */
        $lessons = [];

        foreach ($rows as $row) {
            $cefr = SummerImportOptions::normalizeCefr($row['CEFR Level'] ?? '');
            if (!in_array($cefr, $allowedCefrs, true)) {
                continue;
            }

            $day = (int) preg_replace('/\D/', '', (string) ($row['Day Number'] ?? ''));
            $topic = trim($row['Day / Topic'] ?? '');
            $word = trim($row['Vocabulary Word'] ?? '');

            if ($day <= 0 || $topic === '' || $word === '') {
                continue;
            }

            $key = "{$cefr}|{$day}|{$topic}";
            $lessons[$key] ??= [
                'cefr' => $cefr,
                'day' => $day,
                'topic' => $topic,
                'words' => [],
                'invalid' => [],
            ];

            $validation = $validator->validate($word);
            if (!$validation['valid']) {
                $lessons[$key]['invalid'][] = [
                    'word' => $word,
                    'reason' => $validation['reason'] ?? 'invalid',
                ];
            } elseif (!in_array($word, $lessons[$key]['words'], true)) {
                $lessons[$key]['words'][] = $word;
            }
        }

        $sourceSummaries = [];
        $invalidRows = [];
        $wordCountIssues = [];

        foreach ($allowedCefrs as $cefr) {
            $cefrLessons = collect($lessons)->filter(fn ($lesson) => $lesson['cefr'] === $cefr);
            $summary = [
                'cefr' => $cefr,
                'lessons' => $cefrLessons->count(),
                'invalid_vocab' => 0,
                'word_count_issues' => 0,
            ];

            foreach ($cefrLessons as $lesson) {
                $summary['invalid_vocab'] += count($lesson['invalid']);
                foreach ($lesson['invalid'] as $invalid) {
                    $invalidRows[] = [
                        'cefr' => $cefr,
                        'lesson' => "Day {$lesson['day']}: {$lesson['topic']}",
                        'word' => $invalid['word'],
                        'reason' => $invalid['reason'],
                    ];
                }

                $countValidation = $validator->validateLessonWordCount(count($lesson['words']));
                if (!$countValidation['valid']) {
                    $summary['word_count_issues']++;
                    $wordCountIssues[] = [
                        'cefr' => $cefr,
                        'lesson' => "Day {$lesson['day']}: {$lesson['topic']}",
                        'vocab' => count($lesson['words']),
                        'reason' => $countValidation['reason'],
                    ];
                }
            }

            $sourceSummaries[] = $summary;
        }

        $this->info('Summary by course (XLSX cleaned vocab source)');
        $this->table(
            ['CEFR', 'Lessons in sheet', 'Invalid vocab rows', 'Lessons outside 5–10 words'],
            collect($sourceSummaries)->map(fn ($row) => [
                $row['cefr'],
                $row['lessons'],
                $row['invalid_vocab'],
                $row['word_count_issues'],
            ])->all()
        );

        if ($invalidRows !== []) {
            $this->newLine();
            $this->line('Invalid XLSX vocabulary rows: ' . count($invalidRows));
            $this->table(
                ['CEFR', 'Lesson', 'Word', 'Issue'],
                collect($invalidRows)->take(50)->map(fn ($row) => [
                    $row['cefr'],
                    $row['lesson'],
                    $row['word'],
                    $row['reason'],
                ])->all()
            );
            if (count($invalidRows) > 50) {
                $this->warn('Showing first 50 invalid XLSX rows only.');
            }
        }

        if ($wordCountIssues !== []) {
            $this->newLine();
            $this->line('XLSX lessons outside 5–10 vocabulary words: ' . count($wordCountIssues));
            $this->table(
                ['CEFR', 'Lesson', 'Valid words', 'Issue'],
                collect($wordCountIssues)->map(fn ($row) => [
                    $row['cefr'],
                    $row['lesson'],
                    $row['vocab'],
                    $row['reason'],
                ])->all()
            );
        }
    }

    /**
     * @param list<array<string, string>> $invalidVocab
     * @param list<array<string, mixed>> $wordCountIssues
     * @param list<array<string, int|string>> $emptyLessons
     * @param list<array<string, int|string>> $duplicateSlugs
     */
    private function finish(
        array $invalidVocab,
        array $wordCountIssues,
        array $emptyLessons,
        array $duplicateSlugs,
    ): int {
        $hasIssues = $invalidVocab !== []
            || $wordCountIssues !== []
            || $emptyLessons !== []
            || $duplicateSlugs !== [];

        if ($this->option('strict') && $hasIssues) {
            $this->newLine();
            $this->error('Audit found issues (--strict).');

            return self::FAILURE;
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

        $normalized = SummerImportOptions::normalizeCefr((string) $cefr);
        if (!isset(SummerVocabAssetArchiver::COURSE_SLUGS[$normalized])) {
            return null;
        }

        return [SummerVocabAssetArchiver::COURSE_SLUGS[$normalized]];
    }

    /**
     * @param list<string> $courseSlugs
     * @return list<string>
     */
    private function allowedCefrsFromSlugs(array $courseSlugs): array
    {
        $cefrs = [];
        foreach (SummerVocabAssetArchiver::COURSE_SLUGS as $cefr => $slug) {
            if (in_array($slug, $courseSlugs, true)) {
                $cefrs[] = $cefr;
            }
        }

        return $cefrs;
    }
}
