<?php

namespace App\Services\Import;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\Option;
use App\Models\Organization;
use App\Models\Prompt;
use App\Models\Vocabulary;
use App\Services\Tts\PromptOptionTtsService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class SummerPracticePalImporter
{
    private const VOCAB_SHEET = 'cleaned vocab';
    private const PROMPTS_SHEET = 'fill_in_the_blank_questions.csv';

    /** @var array<string, array{title: string, slug: string, sort_order: int}> */
    private const COURSE_DEFINITIONS = [
        'Pre-A1' => ['title' => 'Summer Practice Pal — Pre-A1', 'slug' => 'summer-practice-pal-pre-a1', 'sort_order' => 1],
        'A1' => ['title' => 'Summer Practice Pal — A1', 'slug' => 'summer-practice-pal-a1', 'sort_order' => 2],
        'A2' => ['title' => 'Summer Practice Pal — A2', 'slug' => 'summer-practice-pal-a2', 'sort_order' => 3],
        'B1' => ['title' => 'Summer Practice Pal — B1', 'slug' => 'summer-practice-pal-b1', 'sort_order' => 4],
    ];

    /** @var list<array<string, string>> */
    private array $vocabRejectedRows = [];

    /** @var list<array<string, string>> */
    private array $promptRejectedRows = [];

    /** @var list<array<string, mixed>> */
    private array $lessonWordCountIssues = [];

    public function __construct(
        private VocabularyEnricher $vocabularyEnricher,
        private LessonGameCreator $lessonGameCreator,
        private PromptFromFillBlankBuilder $promptBuilder,
        private PromptOptionTtsService $promptOptionTtsService,
        private VocabularyWordValidator $vocabularyValidator,
        private SummerVocabAssetArchiver $assetArchiver,
        private ImportCsvReader $csvReader,
    ) {}

    /**
     * @return list<array<string, string>>
     */
    public function vocabRejectedRows(): array
    {
        return $this->vocabRejectedRows;
    }

    /**
     * @return list<array<string, string>>
     */
    public function promptRejectedRows(): array
    {
        return $this->promptRejectedRows;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function lessonWordCountIssues(): array
    {
        return $this->lessonWordCountIssues;
    }

    /**
     * @return array<string, mixed>
     */
    public function import(string $xlsxPath, SummerImportOptions $options, ?SummerImportReporter $reporter = null): array
    {
        if (!is_readable($xlsxPath)) {
            throw new RuntimeException("XLSX file not found or not readable: {$xlsxPath}");
        }

        $reporter ??= new SummerImportReporter();
        $this->reporter = $reporter;
        $this->vocabRejectedRows = [];
        $this->promptRejectedRows = [];
        $this->lessonWordCountIssues = [];

        $reporter->info('Import started', [
            'source' => $xlsxPath,
            'dry_run' => $options->dryRun,
            'cefr' => $options->cefr,
            'structure_only' => $options->isStructureOnly(),
            'force' => $options->force,
            'validated_vocab_csv' => $options->usesValidatedVocabCsv(),
            'prompts_csv' => $options->promptsCsv,
            'strict' => $options->strict,
        ]);

        $reader = new XlsxReader($xlsxPath);
        $reporter->info('Reading spreadsheet');
        $xlsxVocabRows = $reader->readSheet(self::VOCAB_SHEET);
        $xlsxPromptRows = $reader->readSheet(self::PROMPTS_SHEET);

        $vocabRows = $this->loadVocabRows($xlsxVocabRows, $options);
        $promptRows = $this->loadPromptRows($xlsxPromptRows, $options);

        $reporter->info('Sources parsed', [
            'vocab_rows' => count($vocabRows),
            'prompt_rows' => count($promptRows),
            'vocab_rejected' => count($this->vocabRejectedRows),
            'xlsx_vocab_rows' => count($xlsxVocabRows),
            'xlsx_prompt_rows' => count($xlsxPromptRows),
        ]);

        $grouped = $this->groupData($vocabRows, $promptRows, $options);
        $this->auditLessonWordCounts($grouped, $options);

        if ($options->strict && $this->lessonWordCountIssues !== []) {
            $summary = $this->buildDryRunSummary($grouped, $vocabRows, $promptRows, $options);
            $summary['strict_failed'] = true;
            $summary['strict_errors'] = array_column($this->lessonWordCountIssues, 'reason');

            return $summary;
        }

        if ($options->dryRun) {
            $summary = $this->buildDryRunSummary($grouped, $vocabRows, $promptRows, $options);
            $reporter->info('Dry run complete', $summary['totals'] ?? []);

            return $summary;
        }

        $lessonTotal = 0;
        foreach ($grouped as $data) {
            $lessonTotal += count($data['lessons']);
        }
        $reporter->setLessonTotal($lessonTotal);

        $summary = $this->persistImport($grouped, $options);
        $reporter->finish($summary);

        return $summary;
    }

    private ?SummerImportReporter $reporter = null;

    private function reportProgress(string $message, array $context = []): void
    {
        $this->reporter?->info($message, $context);
    }

    /**
     * @param list<array<string, string>> $xlsxVocabRows
     * @return list<array<string, string>>
     */
    private function loadVocabRows(array $xlsxVocabRows, SummerImportOptions $options): array
    {
        $allowedCefrs = $options->cefrLevels();
        $rows = [];

        foreach ($allowedCefrs as $cefr) {
            if (isset($options->vocabCsvByCefr[$cefr])) {
                $csvPath = $options->vocabCsvByCefr[$cefr];
                $this->reportProgress("Loading validated vocab CSV for {$cefr}", ['path' => $csvPath]);
                foreach ($this->csvReader->read($csvPath) as $row) {
                    $processed = $this->processVocabRow($row, $cefr, true);
                    if ($processed !== null) {
                        $rows[] = $processed;
                    }
                }
                continue;
            }

            foreach ($xlsxVocabRows as $row) {
                if (SummerImportOptions::normalizeCefr($row['CEFR Level'] ?? '') !== $cefr) {
                    continue;
                }
                $processed = $this->processVocabRow($row, $cefr, false);
                if ($processed !== null) {
                    $rows[] = $processed;
                }
            }
        }

        return $rows;
    }

    /**
     * @param array<string, string> $row
     * @return array<string, string>|null
     */
    private function processVocabRow(array $row, string $expectedCefr, bool $validate): ?array
    {
        $cefr = SummerImportOptions::normalizeCefr($row['CEFR Level'] ?? $expectedCefr);
        if ($cefr !== $expectedCefr) {
            $this->vocabRejectedRows[] = [
                'cefr' => $cefr,
                'day' => $row['Day Number'] ?? '',
                'topic' => $row['Day / Topic'] ?? '',
                'word' => $row['Vocabulary Word'] ?? '',
                'reason' => "CEFR mismatch (expected {$expectedCefr})",
            ];

            return null;
        }

        $word = trim($row['Vocabulary Word'] ?? '');
        if ($word === '') {
            return null;
        }

        if ($validate) {
            $validation = $this->vocabularyValidator->validate($word);
            if (!$validation['valid']) {
                $this->vocabRejectedRows[] = [
                    'cefr' => $cefr,
                    'day' => $row['Day Number'] ?? '',
                    'topic' => $row['Day / Topic'] ?? '',
                    'word' => $word,
                    'reason' => $validation['reason'] ?? 'invalid',
                ];

                return null;
            }
        }

        return [
            'CEFR Level' => $cefr,
            'Day Number' => $row['Day Number'] ?? '',
            'Day / Topic' => $row['Day / Topic'] ?? '',
            'Vocabulary Word' => $word,
        ];
    }

    /**
     * @param list<array<string, string>> $xlsxPromptRows
     * @return list<array<string, string>>
     */
    private function loadPromptRows(array $xlsxPromptRows, SummerImportOptions $options): array
    {
        if ($options->promptsCsv !== null) {
            $this->reportProgress('Loading prompts CSV (replacing XLSX prompts)', ['path' => $options->promptsCsv]);

            return $this->csvReader->read($options->promptsCsv);
        }

        return $xlsxPromptRows;
    }

    /**
     * @param list<array<string, string>> $vocabRows
     * @param list<array<string, string>> $promptRows
     * @return array<string, array<string, mixed>>
     */
    private function groupData(array $vocabRows, array $promptRows, SummerImportOptions $options): array
    {
        $allowedCefrs = $options->cefrLevels();
        $grouped = [];

        foreach ($allowedCefrs as $cefr) {
            $grouped[$cefr] = [
                'course' => self::COURSE_DEFINITIONS[$cefr],
                'lessons' => [],
            ];
        }

        foreach ($vocabRows as $row) {
            $cefr = SummerImportOptions::normalizeCefr($row['CEFR Level'] ?? '');
            if (!isset($grouped[$cefr])) {
                continue;
            }

            $dayNumber = $this->parseDayNumber($row['Day Number'] ?? '');
            $topic = trim($row['Day / Topic'] ?? '');
            $word = trim($row['Vocabulary Word'] ?? '');

            if ($dayNumber === null || $topic === '' || $word === '') {
                continue;
            }

            $lessonKey = $this->lessonGroupingKey($cefr, $dayNumber, $topic, $options);
            $grouped[$cefr]['lessons'][$lessonKey] ??= $this->emptyLesson($dayNumber, $topic, $cefr, $options);

            $wordKey = $this->normalizeWordKey($word);
            if (isset($grouped[$cefr]['lessons'][$lessonKey]['vocabulary'][$wordKey])) {
                $this->vocabRejectedRows[] = [
                    'cefr' => $cefr,
                    'day' => (string) $dayNumber,
                    'topic' => $topic,
                    'word' => $word,
                    'reason' => 'duplicate within lesson',
                ];
                continue;
            }

            $grouped[$cefr]['lessons'][$lessonKey]['vocabulary'][$wordKey] = $word;
        }

        foreach ($promptRows as $row) {
            $cefr = SummerImportOptions::normalizeCefr($row['CEFR Level'] ?? '');
            if (!isset($grouped[$cefr])) {
                continue;
            }

            $dayNumber = $this->parseDayNumber($row['Day Number'] ?? '');
            $topic = trim($row['Day / Topic'] ?? '');
            $question = trim($row['Question'] ?? '');
            $answer = trim($row['Answer'] ?? '');

            if ($dayNumber === null || $topic === '' || $question === '' || $answer === '') {
                $this->rejectPromptRow($row, 'missing required field');
                continue;
            }

            if (!preg_match('/\{blank\}/i', $question)) {
                $this->rejectPromptRow($row, 'question missing {blank}');
                continue;
            }

            $lessonKey = $this->lessonGroupingKey($cefr, $dayNumber, $topic, $options);
            $grouped[$cefr]['lessons'][$lessonKey] ??= $this->emptyLesson($dayNumber, $topic, $cefr, $options);

            $questionKey = $this->promptBuilder->normalizeQuestionKey($question);
            $grouped[$cefr]['lessons'][$lessonKey]['prompts'][$questionKey] = [
                'question' => $question,
                'answer' => $answer,
            ];
        }

        return $grouped;
    }

    /**
     * @param array<string, string> $row
     */
    private function rejectPromptRow(array $row, string $reason): void
    {
        $this->promptRejectedRows[] = [
            'cefr' => $row['CEFR Level'] ?? '',
            'day' => $row['Day Number'] ?? '',
            'topic' => $row['Day / Topic'] ?? '',
            'question' => Str::limit($row['Question'] ?? '', 80),
            'answer' => $row['Answer'] ?? '',
            'reason' => $reason,
        ];
    }

    /**
     * @param array<string, array<string, mixed>> $grouped
     */
    private function auditLessonWordCounts(array $grouped, SummerImportOptions $options): void
    {
        if (!$options->usesValidatedVocabCsv()) {
            return;
        }

        foreach ($grouped as $cefr => $data) {
            foreach ($data['lessons'] as $lesson) {
                $count = count($lesson['vocabulary']);
                $validation = $this->vocabularyValidator->validateLessonWordCount($count);
                if (!$validation['valid']) {
                    $this->lessonWordCountIssues[] = [
                        'cefr' => $cefr,
                        'lesson' => $lesson['title'],
                        'slug' => $lesson['slug'],
                        'word_count' => $count,
                        'reason' => $validation['reason'],
                    ];
                }
            }
        }
    }

    /**
     * @return array{day_number: int, topic: string, slug: string, title: string, session_number: int, vocabulary: array<string, string>, prompts: array<string, array{question: string, answer: string}>}
     */
    private function emptyLesson(int $dayNumber, string $displayTopic, string $cefr, SummerImportOptions $options): array
    {
        $slugTopic = $this->resolveSlugTopic($cefr, $dayNumber, $displayTopic, $options);

        return [
            'day_number' => $dayNumber,
            'topic' => $displayTopic,
            'title' => $this->formatLessonTitle($dayNumber, $displayTopic),
            'session_number' => $dayNumber,
            'slug' => $this->lessonSlug($cefr, $dayNumber, $slugTopic),
            'vocabulary' => [],
            'prompts' => [],
        ];
    }

    /**
     * @param array<string, array<string, mixed>> $grouped
     * @return array<string, mixed>
     */
    private function buildDryRunSummary(array $grouped, array $vocabRows, array $promptRows, SummerImportOptions $options): array
    {
        $summary = [
            'dry_run' => true,
            'cefr_filter' => $options->cefr,
            'source_vocab_rows' => count($vocabRows),
            'source_prompt_rows' => count($promptRows),
            'vocab_rejected' => count($this->vocabRejectedRows),
            'prompts_rejected' => count($this->promptRejectedRows),
            'lesson_word_count_issues' => $this->lessonWordCountIssues,
            'vocab_csv_files' => $options->vocabCsvByCefr,
            'prompts_csv' => $options->promptsCsv,
            'courses' => [],
            'totals' => [
                'courses' => 0,
                'lessons' => 0,
                'vocabulary_words' => 0,
                'prompts' => 0,
                'lessons_vocab_only' => 0,
            ],
        ];

        foreach ($grouped as $cefr => $data) {
            $lessons = array_values($data['lessons']);
            $lessonCount = count($lessons);
            $vocabCount = array_sum(array_map(fn ($l) => count($l['vocabulary']), $lessons));
            $promptCount = array_sum(array_map(fn ($l) => count($l['prompts']), $lessons));
            $vocabOnly = count(array_filter($lessons, fn ($l) => count($l['vocabulary']) > 0 && count($l['prompts']) === 0));

            $summary['courses'][$cefr] = [
                'slug' => $data['course']['slug'],
                'title' => $data['course']['title'],
                'lessons' => $lessonCount,
                'vocabulary_words' => $vocabCount,
                'prompts' => $promptCount,
                'lessons_vocab_only' => $vocabOnly,
            ];

            $summary['totals']['courses']++;
            $summary['totals']['lessons'] += $lessonCount;
            $summary['totals']['vocabulary_words'] += $vocabCount;
            $summary['totals']['prompts'] += $promptCount;
            $summary['totals']['lessons_vocab_only'] += $vocabOnly;
        }

        return $summary;
    }

    /**
     * @param array<string, array<string, mixed>> $grouped
     * @return array<string, mixed>
     */
    private function persistImport(array $grouped, SummerImportOptions $options): array
    {
        $summary = [
            'dry_run' => false,
            'structure_only' => $options->isStructureOnly(),
            'courses_created' => 0,
            'courses_updated' => 0,
            'lessons_created' => 0,
            'lessons_skipped' => 0,
            'vocabulary_created' => 0,
            'vocabulary_skipped' => 0,
            'prompts_created' => 0,
            'prompts_skipped' => 0,
            'prompts_rejected' => count($this->promptRejectedRows),
            'vocab_rejected' => count($this->vocabRejectedRows),
            'options_created' => 0,
            'games_ensured' => 0,
            'translations_ok' => 0,
            'images_ok' => 0,
            'tts_ok' => 0,
            'vocab_enrichment_errors' => 0,
            'prompt_tts_generated' => 0,
            'lessons_vocab_only' => 0,
            'assets_archived' => 0,
            'lessons_removed' => 0,
            'by_cefr' => [],
        ];

        $rootOrg = Organization::root() ?? Organization::firstOrCreate(
            ['slug' => 'root'],
            [
                'name' => 'Root',
                'description' => 'System-level organization for canonical courses',
                'is_active' => true,
                'access_mode' => 'restricted',
                'is_root' => true,
            ]
        );

        $summerOrg = Organization::firstOrCreate(
            ['slug' => Organization::SUMMER_PRACTICE_PAL_SLUG],
            [
                'name' => 'Summer Practice Pal',
                'description' => 'Summer Practice Pal — login required for CEFR practice courses',
                'is_active' => true,
                'access_mode' => 'restricted',
                'allow_self_registration' => true,
                'registration_type' => Organization::REGISTRATION_TYPE_PARENT_SIGNUP,
                'retain_voice_recordings' => true,
            ]
        );
        $summerOrg->update([
            'name' => 'Summer Practice Pal',
            'access_mode' => 'restricted',
            'allow_self_registration' => true,
            'registration_type' => Organization::REGISTRATION_TYPE_PARENT_SIGNUP,
            'retain_voice_recordings' => true,
        ]);

        $defaultOrg = Organization::where('slug', 'default')->first();

        foreach ($grouped as $cefr => $data) {
            $courseSummary = [
                'lessons' => 0,
                'vocabulary' => 0,
                'prompts' => 0,
            ];

            $this->reportProgress("Course {$cefr}", ['slug' => $data['course']['slug']]);

            DB::transaction(function () use ($cefr, $data, $options, $rootOrg, $summerOrg, $defaultOrg, &$summary, &$courseSummary) {
                $courseDef = $data['course'];
                $course = Course::firstOrNew(['slug' => $courseDef['slug']]);
                $wasNew = !$course->exists;

                $course->fill([
                    'title' => $courseDef['title'],
                    'description' => "Summer Practice Pal content for {$cefr} learners.",
                    'sort_order' => $courseDef['sort_order'],
                    'is_active' => true,
                    'guided_mode_enabled' => true,
                    'guided_flow' => [
                        'vocabulary',
                        'prompts',
                        'matching',
                        'flashcard',
                        'spelling',
                    ],
                ]);
                $course->save();

                if ($wasNew) {
                    $summary['courses_created']++;
                    $this->reportProgress("Created course {$courseDef['title']}", ['course_id' => $course->id]);
                } else {
                    $summary['courses_updated']++;
                    $this->reportProgress("Updated course {$courseDef['title']}", ['course_id' => $course->id]);
                }

                $rootOrg->courses()->syncWithoutDetaching([$course->id => ['is_org_wide' => true]]);
                $summerOrg->courses()->syncWithoutDetaching([$course->id => ['is_org_wide' => true]]);

                if ($options->detachFromDefault && $defaultOrg) {
                    $defaultOrg->courses()->detach($course->id);
                }

                $lessons = $data['lessons'];
                uasort($lessons, fn ($a, $b) => [$a['day_number'], $a['topic']] <=> [$b['day_number'], $b['topic']]);

                if ($options->force && !$options->skipArchive) {
                    $archiveDir = $this->assetArchiver->beginSession();
                    $this->reportProgress('Archive session started', ['path' => $archiveDir]);
                }

                $importedSlugs = [];

                foreach ($lessons as $lessonData) {
                    $lessonResult = $this->importLesson($course, $lessonData, $options);
                    $importedSlugs[] = $lessonData['slug'];
                    $this->reporter?->lessonCompleted(
                        $cefr,
                        $lessonData['title'],
                        $lessonData['slug'],
                        $lessonResult,
                    );
                    $courseSummary['lessons']++;
                    $courseSummary['vocabulary'] += $lessonResult['vocabulary_created'];
                    $courseSummary['prompts'] += $lessonResult['prompts_created'];

                    $summary['lessons_created'] += $lessonResult['lesson_created'] ? 1 : 0;
                    $summary['lessons_skipped'] += $lessonResult['lesson_skipped'] ? 1 : 0;
                    $summary['vocabulary_created'] += $lessonResult['vocabulary_created'];
                    $summary['vocabulary_skipped'] += $lessonResult['vocabulary_skipped'];
                    $summary['prompts_created'] += $lessonResult['prompts_created'];
                    $summary['prompts_skipped'] += $lessonResult['prompts_skipped'];
                    $summary['options_created'] += $lessonResult['options_created'];
                    $summary['games_ensured'] += $lessonResult['games_ensured'] ? 1 : 0;
                    $summary['translations_ok'] += $lessonResult['translations_ok'];
                    $summary['images_ok'] += $lessonResult['images_ok'];
                    $summary['tts_ok'] += $lessonResult['tts_ok'];
                    $summary['vocab_enrichment_errors'] += $lessonResult['vocab_enrichment_errors'];
                    $summary['prompt_tts_generated'] += $lessonResult['prompt_tts_generated'];
                    $summary['assets_archived'] += $lessonResult['assets_archived'];

                    if ($lessonResult['vocabulary_created'] > 0 && $lessonResult['prompts_created'] === 0 && count($lessonData['prompts']) === 0) {
                        $summary['lessons_vocab_only']++;
                    }
                }

                if ($options->force && $this->usesLegacyLessonSlugs($cefr, $options)) {
                    $orphans = Lesson::query()
                        ->where('course_id', $course->id)
                        ->whereNotIn('slug', $importedSlugs)
                        ->get();

                    foreach ($orphans as $orphan) {
                        $this->lessonGameCreator->deleteLesson($orphan);
                        $summary['lessons_removed']++;
                    }

                    if ($orphans->isNotEmpty()) {
                        $this->reportProgress('Removed lessons outside validated import set', [
                            'course' => $cefr,
                            'count' => $orphans->count(),
                        ]);
                    }
                }

                if ($options->force && !$options->skipArchive) {
                    $this->assetArchiver->endSession();
                }
            });

            $summary['by_cefr'][$cefr] = $courseSummary;
        }

        return $summary;
    }

    /**
     * @param array<string, mixed> $lessonData
     * @return array<string, int|bool>
     */
    private function importLesson(Course $course, array $lessonData, SummerImportOptions $options): array
    {
        $result = [
            'lesson_created' => false,
            'lesson_skipped' => false,
            'vocabulary_created' => 0,
            'vocabulary_skipped' => 0,
            'prompts_created' => 0,
            'prompts_skipped' => 0,
            'prompts_rejected' => 0,
            'options_created' => 0,
            'games_ensured' => false,
            'translations_ok' => 0,
            'images_ok' => 0,
            'tts_ok' => 0,
            'vocab_enrichment_errors' => 0,
            'prompt_tts_generated' => 0,
            'assets_archived' => 0,
        ];

        $lesson = Lesson::where('slug', $lessonData['slug'])->first();
        $lessonExisted = $lesson !== null;

        if ($lessonExisted && !$options->force) {
            $result['lesson_skipped'] = true;
            if ($lesson->course_id !== $course->id) {
                $this->reporter?->warn('Lesson exists under different course; leaving unchanged', [
                    'slug' => $lesson->slug,
                    'course_id' => $lesson->course_id,
                    'expected_course_id' => $course->id,
                ]);
            }
        } elseif ($lessonExisted && $options->force) {
            if (!$options->skipArchive) {
                $archiveSummary = $this->assetArchiver->archiveLesson($lesson);
                $result['assets_archived'] = $archiveSummary['vocabulary_rows'];
                $this->reportProgress('Archived vocabulary assets before replace', [
                    'slug' => $lesson->slug,
                    'rows' => $archiveSummary['vocabulary_rows'],
                    'images' => $archiveSummary['images_copied'],
                    'audio' => $archiveSummary['audio_copied'],
                ]);
            }

            $lesson->prompts()->delete();
            $lesson->vocabulary()->delete();
            $this->lessonGameCreator->clearGamesForLesson($lesson);
            $lesson->update([
                'title' => $lessonData['title'],
                'session_number' => $lessonData['session_number'],
                'sort_order' => $lessonData['session_number'],
            ]);
            $result['lesson_skipped'] = true;
        } else {
            $lesson = Lesson::create([
                'slug' => $lessonData['slug'],
                'course_id' => $course->id,
                'title' => $lessonData['title'],
                'session_number' => $lessonData['session_number'],
                'grade_level' => '4-12',
                'is_active' => true,
                'sort_order' => $lessonData['session_number'],
            ]);
            $result['lesson_created'] = true;
        }

        $words = array_values($lessonData['vocabulary']);
        $existingWords = $lesson->vocabulary()
            ->pluck('english_word')
            ->map(fn ($w) => strtolower(trim($w)))
            ->flip();

        $sortOrder = (int) ($lesson->vocabulary()->max('sort_order') ?? 0);

        foreach ($words as $englishWord) {
            $wordKey = strtolower(trim($englishWord));
            if ($existingWords->has($wordKey)) {
                $result['vocabulary_skipped']++;
                continue;
            }

            $sortOrder++;
            $vocabulary = Vocabulary::create([
                'lesson_id' => $lesson->id,
                'english_word' => $englishWord,
                'sort_order' => $sortOrder,
                'is_active' => true,
            ]);
            $existingWords->put($wordKey, true);

            if (!$options->isStructureOnly()) {
                $enrichment = $this->vocabularyEnricher->enrich($vocabulary, $options);
                if ($enrichment['translations_ok']) {
                    $result['translations_ok']++;
                }
                if ($enrichment['images_ok']) {
                    $result['images_ok']++;
                }
                if ($enrichment['tts_ok']) {
                    $result['tts_ok']++;
                }
                if ($enrichment['errors'] !== []) {
                    $result['vocab_enrichment_errors'] += count($enrichment['errors']);
                    foreach ($enrichment['errors'] as $error) {
                        $this->reporter?->warn($error, [
                            'lesson_slug' => $lessonData['slug'],
                            'word' => $englishWord,
                        ]);
                    }
                }
            }

            $result['vocabulary_created']++;
        }

        if ($lesson->vocabulary()->count() >= 2) {
            $this->lessonGameCreator->createGamesForLesson($lesson);
            $result['games_ensured'] = true;
        }

        $prompts = array_values($lessonData['prompts']);
        $existingTemplates = $lesson->prompts()->pluck('template')->flip();

        $lessonVocab = $lesson->vocabulary()->pluck('english_word')->all();
        if ($lessonVocab === []) {
            $lessonVocab = $words;
        }

        $promptSort = (int) ($lesson->prompts()->max('sort_order') ?? 0);
        $createdOptions = [];

        foreach ($prompts as $index => $promptRow) {
            $built = $this->promptBuilder->build(
                $promptRow['question'],
                $promptRow['answer'],
                $lessonVocab,
                $index + 1
            );

            if ($built === null) {
                $result['prompts_rejected']++;
                $this->rejectPromptRow([
                    'CEFR Level' => '',
                    'Day Number' => (string) ($lessonData['day_number'] ?? ''),
                    'Day / Topic' => $lessonData['topic'] ?? '',
                    'Question' => $promptRow['question'],
                    'Answer' => $promptRow['answer'],
                ], 'failed to build prompt');
                continue;
            }

            if ($existingTemplates->has($built['template'])) {
                $result['prompts_skipped']++;
                continue;
            }

            $promptSort++;
            $prompt = Prompt::create([
                'lesson_id' => $lesson->id,
                'prompt_text' => $built['prompt_text'],
                'template' => $built['template'],
                'tts_voice' => 'default',
                'correct_answer' => $built['correct_answer'],
                'sort_order' => $promptSort,
                'is_active' => true,
            ]);
            $existingTemplates->put($built['template'], true);

            $optionOrder = 1;
            foreach ($built['options'] as $optionText) {
                $option = $prompt->options()->create([
                    'label' => $optionText,
                    'image_path' => '',
                    'is_active' => true,
                    'sort_order' => $optionOrder++,
                ]);
                $createdOptions[] = $option;
                $result['options_created']++;
            }

            $result['prompts_created']++;
        }

        if ($options->generateTts && $createdOptions !== []) {
            $result['prompt_tts_generated'] = $this->promptOptionTtsService->generateForOptions($createdOptions);
        }

        return $result;
    }

    private function lessonKey(int $dayNumber, string $topic): string
    {
        return $dayNumber . '|' . strtolower(trim($topic));
    }

    private function lessonGroupingKey(string $cefr, int $dayNumber, string $csvTopic, SummerImportOptions $options): string
    {
        if ($this->usesLegacyLessonSlugs($cefr, $options)) {
            return (string) $dayNumber;
        }

        return $this->lessonKey($dayNumber, $csvTopic);
    }

    private function usesLegacyLessonSlugs(string $cefr, SummerImportOptions $options): bool
    {
        if (!in_array($cefr, ['Pre-A1', 'A1', 'A2', 'B1'], true)) {
            return false;
        }

        return isset($options->vocabCsvByCefr[$cefr]) || $options->promptsCsv !== null;
    }

    private function resolveSlugTopic(string $cefr, int $dayNumber, string $csvTopic, SummerImportOptions $options): string
    {
        if (!$this->usesLegacyLessonSlugs($cefr, $options)) {
            return $csvTopic;
        }

        return match ($cefr) {
            'Pre-A1' => SummerPreA1LegacyTopics::forDay($dayNumber) ?? $csvTopic,
            'A1' => SummerA1LegacyTopics::forDay($dayNumber) ?? $csvTopic,
            'A2' => SummerA2LegacyTopics::forDay($dayNumber) ?? $csvTopic,
            'B1' => SummerB1LegacyTopics::forDay($dayNumber) ?? $csvTopic,
            default => $csvTopic,
        };
    }

    private function formatLessonTitle(int $dayNumber, string $displayTopic): string
    {
        $displayTopic = trim($displayTopic);
        if (preg_match('/^day\s+\d+/i', $displayTopic)) {
            return $displayTopic;
        }

        return "Day {$dayNumber}: {$displayTopic}";
    }

    private function lessonSlug(string $cefr, int $dayNumber, string $topic): string
    {
        $cefrSlug = Str::slug(str_replace('-', ' ', strtolower($cefr)));
        $topicSlug = Str::slug($topic);

        return "summer-practice-pal-{$cefrSlug}-day-{$dayNumber}-{$topicSlug}";
    }

    private function parseDayNumber(string $value): ?int
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        if (!is_numeric($value)) {
            return null;
        }

        return (int) floor((float) $value);
    }

    private function normalizeWordKey(string $word): string
    {
        return strtolower(trim($word));
    }
}
