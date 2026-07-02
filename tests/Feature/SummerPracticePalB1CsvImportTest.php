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
use Tests\TestCase;

class SummerPracticePalB1CsvImportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\OrganizationSeeder::class);
        $this->seed(\Database\Seeders\RootOrganizationSeeder::class);
    }

    public function test_b1_validated_csv_dry_run_has_fifteen_lessons(): void
    {
        $xlsx = base_path('data/Summer Practice Pal Prompts.xlsx');
        if (!is_readable($xlsx)) {
            $this->markTestSkipped('Summer Practice Pal xlsx not present.');
        }

        $summary = app(SummerPracticePalImporter::class)->import($xlsx, new SummerImportOptions(
            dryRun: true,
            strict: true,
            cefr: 'B1',
            vocabCsvByCefr: ['B1' => base_path('data/summer-vocab-b1.csv')],
            promptsCsv: base_path('data/summer-prompts-b1.csv'),
        ));

        $this->assertSame(15, $summary['totals']['lessons']);
        $this->assertSame(135, $summary['totals']['vocabulary_words']);
        $this->assertSame(75, $summary['totals']['prompts']);
        $this->assertSame(0, $summary['vocab_rejected']);
    }

    public function test_b1_force_import_replaces_legacy_slug_and_removes_orphans(): void
    {
        $xlsx = base_path('data/Summer Practice Pal Prompts.xlsx');
        if (!is_readable($xlsx)) {
            $this->markTestSkipped('Summer Practice Pal xlsx not present.');
        }

        $course = Course::create([
            'title' => 'Summer Practice Pal — B1',
            'slug' => SummerVocabAssetArchiver::COURSE_SLUGS['B1'],
            'sort_order' => 4,
            'is_active' => true,
        ]);

        $primary = Lesson::create([
            'course_id' => $course->id,
            'title' => 'Day 1: Introductions & Self-Portraits / Hobbies',
            'slug' => 'summer-practice-pal-b1-day-1-introductions-self-portraits-hobbies',
            'session_number' => 1,
            'grade_level' => '4-12',
            'is_active' => true,
            'sort_order' => 1,
        ]);
        Vocabulary::create([
            'lesson_id' => $primary->id,
            'english_word' => 'Introductions & Self Portraits Day',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $orphan = Lesson::create([
            'course_id' => $course->id,
            'title' => 'Variety Show',
            'slug' => 'summer-practice-pal-b1-orphan-variety-show',
            'session_number' => 5,
            'grade_level' => '4-12',
            'is_active' => true,
            'sort_order' => 5,
        ]);

        $summary = app(SummerPracticePalImporter::class)->import($xlsx, new SummerImportOptions(
            force: true,
            skipArchive: true,
            strict: true,
            cefr: 'B1',
            vocabCsvByCefr: ['B1' => base_path('data/summer-vocab-b1.csv')],
            promptsCsv: base_path('data/summer-prompts-b1.csv'),
        ));

        $primary->refresh();

        $this->assertSame('Day 1: Introductions & Self-Portraits / Hobbies', $primary->title);
        $this->assertSame(
            ['portrait', 'sketch', 'hobby', 'talent', 'personality', 'creative', 'passion', 'interest', 'introduce'],
            $primary->vocabulary()->orderBy('sort_order')->pluck('english_word')->all()
        );
        $this->assertNull(Lesson::find($orphan->id));
        $this->assertGreaterThanOrEqual(1, $summary['lessons_removed']);
        $this->assertGreaterThan(0, Prompt::where('lesson_id', $primary->id)->count());
        $this->assertSame(15, $course->lessons()->count());
    }
}
