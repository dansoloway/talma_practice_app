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

class SummerPracticePalPreA1CsvImportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\OrganizationSeeder::class);
        $this->seed(\Database\Seeders\RootOrganizationSeeder::class);
    }

    public function test_pre_a1_validated_csv_dry_run_has_fifteen_lessons(): void
    {
        $xlsx = base_path('data/Summer Practice Pal Prompts.xlsx');
        if (!is_readable($xlsx)) {
            $this->markTestSkipped('Summer Practice Pal xlsx not present.');
        }

        $summary = app(SummerPracticePalImporter::class)->import($xlsx, new SummerImportOptions(
            dryRun: true,
            strict: true,
            cefr: 'Pre-A1',
            vocabCsvByCefr: ['Pre-A1' => base_path('data/summer-vocab-pre-a1.csv')],
            promptsCsv: base_path('data/summer-prompts-pre-a1.csv'),
        ));

        $this->assertSame(15, $summary['totals']['lessons']);
        $this->assertSame(135, $summary['totals']['vocabulary_words']);
        $this->assertSame(75, $summary['totals']['prompts']);
        $this->assertSame(0, $summary['vocab_rejected']);
    }

    public function test_pre_a1_force_import_updates_legacy_slug_and_deactivates_orphans(): void
    {
        $xlsx = base_path('data/Summer Practice Pal Prompts.xlsx');
        if (!is_readable($xlsx)) {
            $this->markTestSkipped('Summer Practice Pal xlsx not present.');
        }

        $course = Course::create([
            'title' => 'Summer Practice Pal — Pre-A1',
            'slug' => SummerVocabAssetArchiver::COURSE_SLUGS['Pre-A1'],
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $primary = Lesson::create([
            'course_id' => $course->id,
            'title' => 'Introductions Day',
            'slug' => 'summer-practice-pal-pre-a1-day-1-introductions-day',
            'session_number' => 1,
            'grade_level' => '4-12',
            'is_active' => true,
            'sort_order' => 1,
        ]);
        Vocabulary::create([
            'lesson_id' => $primary->id,
            'english_word' => 'Anna is my sister.',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $orphan = Lesson::create([
            'course_id' => $course->id,
            'title' => 'Team Building Day',
            'slug' => 'summer-practice-pal-pre-a1-day-2-team-building-day',
            'session_number' => 2,
            'grade_level' => '4-12',
            'is_active' => true,
            'sort_order' => 2,
        ]);

        $summary = app(SummerPracticePalImporter::class)->import($xlsx, new SummerImportOptions(
            force: true,
            skipArchive: true,
            strict: true,
            cefr: 'Pre-A1',
            vocabCsvByCefr: ['Pre-A1' => base_path('data/summer-vocab-pre-a1.csv')],
            promptsCsv: base_path('data/summer-prompts-pre-a1.csv'),
        ));

        $primary->refresh();
        $orphan->refresh();

        $this->assertSame('Day 1: Introductions Day', $primary->title);
        $this->assertSame(['hello', 'goodbye', 'please', 'yes', 'no', 'thanks', 'name', 'friend'], $primary->vocabulary()->orderBy('sort_order')->pluck('english_word')->all());
        $this->assertFalse($orphan->is_active);
        $this->assertGreaterThanOrEqual(1, $summary['lessons_deactivated']);
        $this->assertGreaterThan(0, Prompt::where('lesson_id', $primary->id)->count());
    }
}
