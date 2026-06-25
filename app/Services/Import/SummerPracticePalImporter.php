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
use Illuminate\Support\Facades\Log;
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

    public function __construct(
        private VocabularyEnricher $vocabularyEnricher,
        private LessonGameCreator $lessonGameCreator,
        private PromptFromFillBlankBuilder $promptBuilder,
        private PromptOptionTtsService $promptOptionTtsService,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function import(string $xlsxPath, SummerImportOptions $options): array
    {
        if (!is_readable($xlsxPath)) {
            throw new RuntimeException("XLSX file not found or not readable: {$xlsxPath}");
        }

        $reader = new XlsxReader($xlsxPath);
        $vocabRows = $reader->readSheet(self::VOCAB_SHEET);
        $promptRows = $reader->readSheet(self::PROMPTS_SHEET);

        $grouped = $this->groupData($vocabRows, $promptRows, $options);

        if ($options->dryRun) {
            return $this->buildDryRunSummary($grouped, $vocabRows, $promptRows, $options);
        }

        return $this->persistImport($grouped, $options);
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

            $lessonKey = $this->lessonKey($dayNumber, $topic);
            $grouped[$cefr]['lessons'][$lessonKey] ??= $this->emptyLesson($dayNumber, $topic, $cefr);
            $grouped[$cefr]['lessons'][$lessonKey]['vocabulary'][$this->normalizeWordKey($word)] = $word;
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
                continue;
            }

            $lessonKey = $this->lessonKey($dayNumber, $topic);
            $grouped[$cefr]['lessons'][$lessonKey] ??= $this->emptyLesson($dayNumber, $topic, $cefr);

            $questionKey = $this->promptBuilder->normalizeQuestionKey($question);
            $grouped[$cefr]['lessons'][$lessonKey]['prompts'][$questionKey] = [
                'question' => $question,
                'answer' => $answer,
            ];
        }

        return $grouped;
    }

    /**
     * @return array{day_number: int, topic: string, slug: string, title: string, session_number: int, vocabulary: array<string, string>, prompts: array<string, array{question: string, answer: string}>}
     */
    private function emptyLesson(int $dayNumber, string $topic, string $cefr): array
    {
        return [
            'day_number' => $dayNumber,
            'topic' => $topic,
            'title' => $topic,
            'session_number' => $dayNumber,
            'slug' => $this->lessonSlug($cefr, $dayNumber, $topic),
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
            'courses_created' => 0,
            'courses_updated' => 0,
            'lessons_created' => 0,
            'lessons_updated' => 0,
            'vocabulary_created' => 0,
            'prompts_created' => 0,
            'options_created' => 0,
            'translations_ok' => 0,
            'images_ok' => 0,
            'tts_ok' => 0,
            'vocab_enrichment_errors' => 0,
            'prompt_tts_generated' => 0,
            'lessons_vocab_only' => 0,
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
            ]
        );
        $summerOrg->update([
            'name' => 'Summer Practice Pal',
            'access_mode' => 'restricted',
            'allow_self_registration' => true,
        ]);

        $defaultOrg = Organization::where('slug', 'default')->first();

        foreach ($grouped as $cefr => $data) {
            $courseSummary = [
                'lessons' => 0,
                'vocabulary' => 0,
                'prompts' => 0,
            ];

            DB::transaction(function () use ($cefr, $data, $options, $rootOrg, $summerOrg, $defaultOrg, &$summary, &$courseSummary) {
                $courseDef = $data['course'];
                $course = Course::firstOrNew(['slug' => $courseDef['slug']]);
                $wasNew = !$course->exists;

                $course->fill([
                    'title' => $courseDef['title'],
                    'description' => "Summer Practice Pal content for {$cefr} learners.",
                    'sort_order' => $courseDef['sort_order'],
                    'is_active' => true,
                ]);
                $course->save();

                if ($wasNew) {
                    $summary['courses_created']++;
                } else {
                    $summary['courses_updated']++;
                }

                $rootOrg->courses()->syncWithoutDetaching([$course->id => ['is_org_wide' => true]]);
                $summerOrg->courses()->syncWithoutDetaching([$course->id => ['is_org_wide' => true]]);

                if ($options->detachFromDefault && $defaultOrg) {
                    $defaultOrg->courses()->detach($course->id);
                }

                $lessons = $data['lessons'];
                uasort($lessons, fn ($a, $b) => [$a['day_number'], $a['topic']] <=> [$b['day_number'], $b['topic']]);

                foreach ($lessons as $lessonData) {
                    $lessonResult = $this->importLesson($course, $lessonData, $options);
                    $courseSummary['lessons']++;
                    $courseSummary['vocabulary'] += $lessonResult['vocabulary_created'];
                    $courseSummary['prompts'] += $lessonResult['prompts_created'];

                    $summary['lessons_created'] += $lessonResult['lesson_created'] ? 1 : 0;
                    $summary['lessons_updated'] += $lessonResult['lesson_created'] ? 0 : 1;
                    $summary['vocabulary_created'] += $lessonResult['vocabulary_created'];
                    $summary['prompts_created'] += $lessonResult['prompts_created'];
                    $summary['options_created'] += $lessonResult['options_created'];
                    $summary['translations_ok'] += $lessonResult['translations_ok'];
                    $summary['images_ok'] += $lessonResult['images_ok'];
                    $summary['tts_ok'] += $lessonResult['tts_ok'];
                    $summary['vocab_enrichment_errors'] += $lessonResult['vocab_enrichment_errors'];
                    $summary['prompt_tts_generated'] += $lessonResult['prompt_tts_generated'];

                    if ($lessonResult['vocabulary_created'] > 0 && $lessonResult['prompts_created'] === 0 && count($lessonData['prompts']) === 0) {
                        $summary['lessons_vocab_only']++;
                    }
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
            'vocabulary_created' => 0,
            'prompts_created' => 0,
            'options_created' => 0,
            'translations_ok' => 0,
            'images_ok' => 0,
            'tts_ok' => 0,
            'vocab_enrichment_errors' => 0,
            'prompt_tts_generated' => 0,
        ];

        $lesson = Lesson::firstOrNew(['slug' => $lessonData['slug']]);
        $result['lesson_created'] = !$lesson->exists;

        $lesson->fill([
            'course_id' => $course->id,
            'title' => $lessonData['title'],
            'session_number' => $lessonData['session_number'],
            'grade_level' => '4-12',
            'is_active' => true,
            'sort_order' => $lessonData['session_number'],
        ]);
        $lesson->save();

        $words = array_values($lessonData['vocabulary']);
        $existingWordCount = $lesson->vocabulary()->count();
        $existingPromptCount = $lesson->prompts()->count();

        if ($options->force && ($existingWordCount > 0 || $existingPromptCount > 0)) {
            $lesson->prompts()->delete();
            $lesson->vocabulary()->delete();
            $this->lessonGameCreator->clearGamesForLesson($lesson);
        } elseif (!$options->force && $existingWordCount > 0 && count($words) > 0) {
            Log::info("Skipping vocab re-import for existing lesson {$lesson->slug}; use --force to replace");
            $words = [];
        }

        $sortOrder = 1;
        foreach ($words as $englishWord) {
            if (!$options->force) {
                $exists = $lesson->vocabulary()->where('english_word', $englishWord)->exists();
                if ($exists) {
                    continue;
                }
            }

            $vocabulary = Vocabulary::create([
                'lesson_id' => $lesson->id,
                'english_word' => $englishWord,
                'sort_order' => $sortOrder++,
                'is_active' => true,
            ]);

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
            }

            $result['vocabulary_created']++;
        }

        if ($result['vocabulary_created'] > 0) {
            $this->lessonGameCreator->createGamesForLesson($lesson);
        }

        $prompts = array_values($lessonData['prompts']);
        if (!$options->force && $existingPromptCount > 0 && count($prompts) > 0) {
            Log::info("Skipping prompt re-import for existing lesson {$lesson->slug}; use --force to replace");
            return $result;
        }

        $lessonVocab = $lesson->vocabulary()->pluck('english_word')->all();
        if ($lessonVocab === []) {
            $lessonVocab = $words;
        }

        $promptSort = 1;
        $createdOptions = [];

        foreach ($prompts as $index => $promptRow) {
            $built = $this->promptBuilder->build(
                $promptRow['question'],
                $promptRow['answer'],
                $lessonVocab,
                $index + 1
            );

            if ($built === null) {
                continue;
            }

            $prompt = Prompt::create([
                'lesson_id' => $lesson->id,
                'prompt_text' => $built['prompt_text'],
                'template' => $built['template'],
                'tts_voice' => 'default',
                'correct_answer' => $built['correct_answer'],
                'sort_order' => $promptSort++,
                'is_active' => true,
            ]);

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
