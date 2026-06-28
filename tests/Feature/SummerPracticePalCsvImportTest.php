<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\Prompt;
use App\Models\Vocabulary;
use App\Services\Import\SummerImportOptions;
use App\Services\Import\SummerPracticePalImporter;
use App\Services\Import\SummerVocabAssetArchiver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SummerPracticePalCsvImportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\OrganizationSeeder::class);
        $this->seed(\Database\Seeders\RootOrganizationSeeder::class);
    }

    public function test_validated_vocab_csv_rejects_invalid_rows_on_dry_run(): void
    {
        $xlsx = base_path('data/Summer Practice Pal Prompts.xlsx');
        if (!is_readable($xlsx)) {
            $this->markTestSkipped('Summer Practice Pal xlsx not present.');
        }

        $options = new SummerImportOptions(
            dryRun: true,
            cefr: 'Pre-A1',
            vocabCsvByCefr: ['Pre-A1' => base_path('tests/fixtures/summer/vocab-invalid.csv')],
        );

        $importer = app(SummerPracticePalImporter::class);
        $summary = $importer->import($xlsx, $options);

        $this->assertGreaterThan(0, $summary['vocab_rejected']);
        $this->assertNotEmpty($importer->vocabRejectedRows());
    }

    public function test_strict_mode_fails_when_lesson_has_too_few_words(): void
    {
        $xlsx = base_path('data/Summer Practice Pal Prompts.xlsx');
        if (!is_readable($xlsx)) {
            $this->markTestSkipped('Summer Practice Pal xlsx not present.');
        }

        $options = new SummerImportOptions(
            dryRun: true,
            strict: true,
            cefr: 'Pre-A1',
            vocabCsvByCefr: ['Pre-A1' => base_path('tests/fixtures/summer/vocab-pre-a1-day1.csv')],
        );

        $summary = app(SummerPracticePalImporter::class)->import($xlsx, $options);

        $this->assertTrue($summary['strict_failed'] ?? false);
    }

    public function test_force_import_with_vocab_csv_replaces_lesson_vocabulary(): void
    {
        $xlsx = base_path('data/Summer Practice Pal Prompts.xlsx');
        if (!is_readable($xlsx)) {
            $this->markTestSkipped('Summer Practice Pal xlsx not present.');
        }

        $importer = app(SummerPracticePalImporter::class);
        $importer->import($xlsx, new SummerImportOptions(cefr: 'Pre-A1'));

        $lesson = Lesson::where('slug', 'like', 'summer-practice-pal-pre-a1-day-1-%')->first();
        $this->assertNotNull($lesson);
        $this->assertGreaterThan(0, $lesson->vocabulary()->count());

        $options = new SummerImportOptions(
            force: true,
            skipArchive: true,
            cefr: 'Pre-A1',
            vocabCsvByCefr: ['Pre-A1' => base_path('tests/fixtures/summer/vocab-pre-a1-day1.csv')],
        );

        $summary = $importer->import($xlsx, $options);

        $lesson->refresh();
        $words = $lesson->vocabulary()->orderBy('sort_order')->pluck('english_word')->all();

        $this->assertSame(['hello', 'goodbye', 'please', 'thanks', 'yes'], $words);
        $this->assertGreaterThan(0, $summary['vocabulary_created']);
    }

    public function test_prompts_csv_creates_sentence_completion_prompts(): void
    {
        $xlsx = base_path('data/Summer Practice Pal Prompts.xlsx');
        if (!is_readable($xlsx)) {
            $this->markTestSkipped('Summer Practice Pal xlsx not present.');
        }

        $vocabCsv = base_path('tests/fixtures/summer/vocab-a2-day4.csv');
        if (!is_readable($vocabCsv)) {
            $this->markTestSkipped('A2 day 4 vocab fixture not present.');
        }

        $importer = app(SummerPracticePalImporter::class);
        $options = new SummerImportOptions(
            force: true,
            skipArchive: true,
            cefr: 'A2',
            vocabCsvByCefr: ['A2' => $vocabCsv],
            promptsCsv: base_path('tests/fixtures/summer/prompts-a2-day4.csv'),
        );

        $summary = $importer->import($xlsx, $options);

        $lesson = Lesson::where('slug', 'like', 'summer-practice-pal-a2-day-4-%')->first();
        $this->assertNotNull($lesson);
        $this->assertGreaterThanOrEqual(5, $lesson->vocabulary()->count());
        $this->assertGreaterThan(0, Prompt::where('lesson_id', $lesson->id)->count());
        $this->assertGreaterThan(0, $summary['prompts_created']);
    }
}
