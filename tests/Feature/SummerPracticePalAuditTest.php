<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\Vocabulary;
use App\Services\Import\SummerVocabAssetArchiver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SummerPracticePalAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_audit_flags_invalid_vocabulary_and_lists_words(): void
    {
        $course = Course::create([
            'title' => 'Summer Practice Pal — A2',
            'slug' => SummerVocabAssetArchiver::COURSE_SLUGS['A2'],
            'sort_order' => 1,
            'is_active' => true,
        ]);
        $lesson = Lesson::create([
            'course_id' => $course->id,
            'title' => 'Day 1: All About Me',
            'slug' => 'summer-practice-pal-a2-day-1-test',
            'session_number' => 1,
            'grade_level' => '4-12',
            'is_active' => true,
            'sort_order' => 1,
        ]);
        Vocabulary::create([
            'lesson_id' => $lesson->id,
            'english_word' => 'friendly',
            'sort_order' => 1,
            'is_active' => true,
        ]);
        Vocabulary::create([
            'lesson_id' => $lesson->id,
            'english_word' => 'Talent Show Event of the Day',
            'sort_order' => 2,
            'is_active' => true,
        ]);

        $this->artisan('talma:summer-practice-pal-audit', ['--cefr' => 'A2', '--list-vocab' => true])
            ->expectsOutputToContain('Invalid vocabulary entries (sentences, phrases, activity titles): 1')
            ->expectsOutputToContain('Talent Show Event of the Day')
            ->expectsOutputToContain('Day 1: All About Me')
            ->assertSuccessful();
    }
}
