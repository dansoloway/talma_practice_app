<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\Vocabulary;
use App\Services\Import\SummerVocabAssetArchiver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SummerVocabAssetArchiverTest extends TestCase
{
    use RefreshDatabase;

    public function test_archives_vocabulary_image_and_audio_with_manifest(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('images/vocabulary/test-word.png', 'fake-image');
        Storage::disk('public')->put('vocabulary-audio/test-word.mp3', 'fake-audio');

        $course = Course::create([
            'title' => 'Summer Practice Pal — Pre-A1',
            'slug' => 'summer-practice-pal-pre-a1',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $lesson = Lesson::create([
            'course_id' => $course->id,
            'title' => 'Test Lesson',
            'slug' => 'summer-practice-pal-pre-a1-day-99-test',
            'session_number' => 99,
            'grade_level' => '4-12',
            'is_active' => true,
            'sort_order' => 99,
        ]);

        $vocabulary = Vocabulary::create([
            'lesson_id' => $lesson->id,
            'english_word' => 'hello',
            'hebrew_translation' => 'שלום',
            'arabic_translation' => 'مرحبا',
            'image_path' => 'images/vocabulary/test-word.png',
            'word_audio_path' => 'vocabulary-audio/test-word.mp3',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $archiver = app(SummerVocabAssetArchiver::class);
        $summary = $archiver->archiveAll(['summer-practice-pal-pre-a1'], 'test');

        $this->assertSame(1, $summary['vocabulary_rows']);
        $this->assertSame(1, $summary['images_copied']);
        $this->assertSame(1, $summary['audio_copied']);
        $this->assertFileExists($summary['manifest_path']);

        $manifest = file_get_contents($summary['manifest_path']);
        $this->assertStringContainsString('hello', $manifest);

        $this->assertFileExists($summary['archive_dir'] . '/images/' . $vocabulary->id . '_test-word.png');
        $this->assertFileExists($summary['archive_dir'] . '/audio/' . $vocabulary->id . '_test-word.mp3');
        Storage::disk('public')->assertExists('images/vocabulary/test-word.png');
    }
}
