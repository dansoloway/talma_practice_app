<?php

namespace Tests\Unit;

use App\Models\ActivityEvent;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\MatchingGame;
use App\Models\Prompt;
use App\Models\Vocabulary;
use App\Services\LessonProgressCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LessonProgressCalculatorTest extends TestCase
{
    use RefreshDatabase;

    public function test_calculates_completion_from_completed_activity_events(): void
    {
        $course = Course::create([
            'title' => 'Test Course',
            'slug' => 'test-course',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $lesson = Lesson::create([
            'course_id' => $course->id,
            'title' => 'Day 1: Hello',
            'slug' => 'day-1-hello',
            'session_number' => 1,
            'grade_level' => '4-12',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $vocab = Vocabulary::create([
            'lesson_id' => $lesson->id,
            'english_word' => 'hello',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        Prompt::create([
            'lesson_id' => $lesson->id,
            'prompt_text' => 'Question',
            'template' => 'template',
            'correct_answer' => 'hello',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        MatchingGame::create([
            'lesson_id' => $lesson->id,
            'title' => 'Matching',
            'vocabulary_ids' => [$vocab->id],
            'is_active' => true,
        ]);

        $lesson->load(['prompts', 'matchingGames', 'flashcardGames', 'spellingGames']);

        $sessionId = '11111111-1111-1111-1111-111111111111';

        ActivityEvent::create([
            'session_id' => $sessionId,
            'lesson_id' => $lesson->id,
            'activity_type' => 'prompts',
            'activity_id' => null,
            'status' => 'completed',
        ]);

        $calculator = app(LessonProgressCalculator::class);
        $percents = $calculator->completionPercentsForLessons(collect([$lesson]), $sessionId);

        $this->assertSame(50, $percents[$lesson->id]);
    }

    public function test_guided_course_percent_uses_flow_steps_not_per_word_vocab_events(): void
    {
        $course = Course::create([
            'title' => 'Guided Progress Course',
            'slug' => 'guided-progress-course',
            'sort_order' => 2,
            'is_active' => true,
            'guided_mode_enabled' => true,
            'guided_flow' => ['vocabulary', 'prompts', 'matching'],
        ]);

        $lesson = Lesson::create([
            'course_id' => $course->id,
            'title' => 'Guided Day 1',
            'slug' => 'guided-day-1',
            'session_number' => 1,
            'grade_level' => '4-12',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $vocabA = Vocabulary::create([
            'lesson_id' => $lesson->id,
            'english_word' => 'hello',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $vocabB = Vocabulary::create([
            'lesson_id' => $lesson->id,
            'english_word' => 'goodbye',
            'sort_order' => 2,
            'is_active' => true,
        ]);

        Prompt::create([
            'lesson_id' => $lesson->id,
            'prompt_text' => 'Question',
            'template' => 'template',
            'correct_answer' => 'hello',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        MatchingGame::create([
            'lesson_id' => $lesson->id,
            'title' => 'Matching',
            'vocabulary_ids' => [$vocabA->id, $vocabB->id],
            'is_active' => true,
        ]);

        $lesson->load(['course', 'vocabulary', 'prompts', 'matchingGames', 'flashcardGames', 'spellingGames']);

        $sessionId = '22222222-2222-2222-2222-222222222222';

        foreach ([$vocabA->id, $vocabB->id] as $vocabId) {
            ActivityEvent::create([
                'session_id' => $sessionId,
                'lesson_id' => $lesson->id,
                'activity_type' => 'vocabulary',
                'activity_id' => $vocabId,
                'status' => 'completed',
            ]);
        }

        $calculator = app(LessonProgressCalculator::class);
        $percents = $calculator->completionPercentsForLessons(collect([$lesson]), $sessionId);

        $this->assertSame(33, $percents[$lesson->id]);
    }

    public function test_student_card_title_strips_redundant_day_prefix(): void
    {
        $lesson = new Lesson([
            'title' => 'Day 12: Show-and-Tell Day',
            'session_number' => 12,
        ]);

        $this->assertSame('Show-and-Tell Day', $lesson->studentCardTitle());
        $this->assertSame('Day 12', $lesson->studentCardLabel());
    }
}
