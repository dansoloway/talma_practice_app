<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\Vocabulary;
use App\Services\Import\SummerImportOptions;
use App\Services\Import\SummerPracticePalImporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SummerPracticePalImporterTest extends TestCase
{
    use RefreshDatabase;

    public function test_structure_import_is_idempotent_and_skips_existing_lessons(): void
    {
        $xlsx = base_path('data/Summer Practice Pal Prompts.xlsx');
        if (!is_readable($xlsx)) {
            $this->markTestSkipped('Summer Practice Pal xlsx not present.');
        }

        $this->seed(\Database\Seeders\OrganizationSeeder::class);
        $this->seed(\Database\Seeders\RootOrganizationSeeder::class);

        $options = new SummerImportOptions(cefr: 'Pre-A1');
        $importer = app(SummerPracticePalImporter::class);

        $first = $importer->import($xlsx, $options);
        $this->assertGreaterThan(0, $first['lessons_created']);
        $this->assertTrue($first['structure_only']);

        $lessonCountAfterFirst = Lesson::count();
        $course = Course::where('slug', 'summer-practice-pal-pre-a1')->first();
        $this->assertNotNull($course);

        $second = $importer->import($xlsx, $options);
        $this->assertSame(0, $second['lessons_created']);
        $this->assertGreaterThan(0, $second['lessons_skipped']);
        $this->assertSame($lessonCountAfterFirst, Lesson::count());
        $this->assertSame(0, $second['vocabulary_created']);
        $this->assertGreaterThan(0, $second['vocabulary_skipped']);
    }

    public function test_import_adds_missing_vocabulary_to_existing_lesson(): void
    {
        $xlsx = base_path('data/Summer Practice Pal Prompts.xlsx');
        if (!is_readable($xlsx)) {
            $this->markTestSkipped('Summer Practice Pal xlsx not present.');
        }

        $this->seed(\Database\Seeders\OrganizationSeeder::class);
        $this->seed(\Database\Seeders\RootOrganizationSeeder::class);

        $options = new SummerImportOptions(cefr: 'Pre-A1');
        $importer = app(SummerPracticePalImporter::class);
        $importer->import($xlsx, $options);

        $lesson = Lesson::where('slug', 'like', 'summer-practice-pal-pre-a1-day-1-%')->first();
        $this->assertNotNull($lesson);

        Vocabulary::where('lesson_id', $lesson->id)->delete();

        $again = $importer->import($xlsx, $options);
        $this->assertGreaterThan(0, $again['vocabulary_created']);
        $this->assertGreaterThan(0, $lesson->fresh()->vocabulary()->count());
    }
}
