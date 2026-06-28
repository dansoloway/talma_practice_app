<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\FlashcardGame;
use App\Models\Lesson;
use App\Models\MatchingGame;
use App\Models\Organization;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_homepage_loads_and_shows_courses(): void
    {
        $response = $this->get('/');
        $response->assertOk();
        $response->assertSee('TALMA Practice Pal');
        $response->assertSee('Choose Your Course');
    }

    public function test_lessons_index_loads(): void
    {
        $response = $this->get(route('lessons.index'));
        $response->assertOk();
    }

    public function test_course_page_shows_lessons(): void
    {
        $course = Course::whereNull('archived_at')->where('is_active', true)->first();
        if (!$course) {
            $this->markTestSkipped('No active course');
        }
        $response = $this->get(route('student.course', $course->slug));
        $response->assertOk();
        $response->assertSee($course->title);
    }

    public function test_lesson_page_loads_with_content(): void
    {
        $lesson = Lesson::where('is_active', true)->first();
        if (!$lesson) {
            $this->markTestSkipped('No active lesson');
        }
        $response = $this->get(route('lessons.show', $lesson->slug));
        $response->assertOk();
        $response->assertSee($lesson->title);
    }

    public function test_non_existent_lesson_returns_404(): void
    {
        $response = $this->get(route('lessons.show', 'non-existent-slug-xyz'));
        $response->assertNotFound();
    }

    public function test_non_existent_course_returns_404(): void
    {
        $response = $this->get(route('student.course', 'non-existent-course-xyz'));
        $response->assertNotFound();
    }

    public function test_org_student_index_shows_courses(): void
    {
        $org = Organization::where('slug', 'default')->firstOrFail();
        $response = $this->get(route('org.student.index', $org->slug));
        $response->assertOk();
        $response->assertSee('Choose Your Course');
    }

    public function test_matching_game_play_route_exists(): void
    {
        $lesson = Lesson::where('is_active', true)->first();
        if (!$lesson) {
            $this->markTestSkipped('No active lesson');
        }
        $game = $lesson->matchingGames()->where('is_active', true)->first();
        if (!$game) {
            $game = MatchingGame::create([
                'lesson_id' => $lesson->id,
                'title' => 'Test Matching Game',
                'vocabulary_ids' => [],
                'is_active' => true,
            ]);
        }
        $response = $this->get(route('matching-games.play', [$lesson, $game]));
        $response->assertOk();
    }

    public function test_game_play_hides_admin_edit_links_for_students(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $lesson = Lesson::where('is_active', true)->first();
        if (!$lesson) {
            $this->markTestSkipped('No active lesson');
        }
        $game = $lesson->matchingGames()->where('is_active', true)->first();
        if (!$game) {
            $game = MatchingGame::create([
                'lesson_id' => $lesson->id,
                'title' => 'Test Matching Game',
                'vocabulary_ids' => [],
                'is_active' => true,
            ]);
        }

        $response = $this->actingAs($student, 'admin')
            ->get(route('matching-games.play', [$lesson, $game]));

        $response->assertOk();
        $response->assertDontSee('Edit Lesson', false);
        $response->assertDontSee('Edit Game', false);
    }

    public function test_game_play_shows_admin_edit_links_for_teachers(): void
    {
        $teacher = User::factory()->teacher()->create();
        $lesson = Lesson::where('is_active', true)->first();
        if (!$lesson) {
            $this->markTestSkipped('No active lesson');
        }
        $game = $lesson->matchingGames()->where('is_active', true)->first();
        if (!$game) {
            $game = MatchingGame::create([
                'lesson_id' => $lesson->id,
                'title' => 'Test Matching Game',
                'vocabulary_ids' => [],
                'is_active' => true,
            ]);
        }

        $response = $this->actingAs($teacher, 'admin')
            ->get(route('matching-games.play', [$lesson, $game]));

        $response->assertOk();
        $response->assertSee('Edit Lesson', false);
        $response->assertSee('Edit Game', false);
    }

    public function test_flashcard_game_play_route_exists(): void
    {
        $lesson = Lesson::where('is_active', true)->first();
        if (!$lesson) {
            $this->markTestSkipped('No active lesson');
        }
        $game = $lesson->flashcardGames()->where('is_active', true)->first();
        if (!$game) {
            $game = FlashcardGame::create([
                'lesson_id' => $lesson->id,
                'title' => 'Test Flashcard Game',
                'game_types' => ['image_to_word'],
                'vocabulary_ids' => [],
                'is_active' => true,
            ]);
        }
        $response = $this->get(route('flashcard-games.play', [$lesson, $game]));
        $response->assertOk();
    }
}
