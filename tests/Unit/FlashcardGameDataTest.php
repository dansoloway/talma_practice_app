<?php

namespace Tests\Unit;

use App\Http\Controllers\Admin\FlashcardGameController;
use App\Models\Course;
use App\Models\FlashcardGame;
use App\Models\Lesson;
use App\Models\Vocabulary;
use App\Services\CourseAccess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionClass;
use Tests\TestCase;

class FlashcardGameDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_audio_only_vocabulary_still_produces_flashcard_deck(): void
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
            'image_path' => null,
            'word_audio_path' => 'vocabulary/audio/hello.mp3',
            'hebrew_translation' => null,
            'arabic_translation' => null,
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $game = FlashcardGame::create([
            'lesson_id' => $lesson->id,
            'title' => 'Audio Flashcards',
            'game_types' => ['audio_to_word'],
            'vocabulary_ids' => [$vocab->id],
            'cards_per_game' => 1,
            'is_active' => true,
        ]);

        $controller = new FlashcardGameController(app(CourseAccess::class));
        $reflection = new ReflectionClass($controller);
        $method = $reflection->getMethod('generateGameData');
        $method->setAccessible(true);

        $gameData = $method->invoke($controller, $game, collect([$vocab]), 'image');

        $this->assertCount(1, $gameData['cards']);
        $this->assertSame(['audio_to_word'], $gameData['game_types']);
        $this->assertArrayHasKey('image', $gameData['available_modes']);
    }
}
