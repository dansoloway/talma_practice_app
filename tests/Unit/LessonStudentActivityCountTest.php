<?php

namespace Tests\Unit;

use App\Models\Course;
use App\Models\FlashcardGame;
use App\Models\Lesson;
use App\Models\MatchingGame;
use App\Models\Prompt;
use App\Models\SpellingGame;
use App\Models\Vocabulary;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LessonStudentActivityCountTest extends TestCase
{
    use RefreshDatabase;

    public function test_counts_prompt_group_once_plus_each_active_game(): void
    {
        $course = Course::create([
            'title' => 'Test Course',
            'slug' => 'test-course',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $lesson = Lesson::create([
            'course_id' => $course->id,
            'title' => 'Test Lesson',
            'slug' => 'test-lesson',
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

        foreach (range(1, 5) as $i) {
            Prompt::create([
                'lesson_id' => $lesson->id,
                'prompt_text' => "Question {$i}",
                'template' => "template-{$i}",
                'correct_answer' => 'hello',
                'sort_order' => $i,
                'is_active' => true,
            ]);
        }

        MatchingGame::create([
            'lesson_id' => $lesson->id,
            'title' => 'Matching',
            'vocabulary_ids' => [$vocab->id],
            'is_active' => true,
        ]);

        FlashcardGame::create([
            'lesson_id' => $lesson->id,
            'title' => 'Flashcards',
            'vocabulary_ids' => [$vocab->id],
            'game_types' => ['image_to_word'],
            'cards_per_game' => 5,
            'is_active' => true,
        ]);

        SpellingGame::create([
            'lesson_id' => $lesson->id,
            'title' => 'Spelling',
            'vocabulary_ids' => [$vocab->id],
            'difficulty' => 'medium',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $lesson->load(['prompts', 'matchingGames', 'flashcardGames', 'spellingGames']);

        $this->assertSame(4, $lesson->studentActivityCount());
    }
}
