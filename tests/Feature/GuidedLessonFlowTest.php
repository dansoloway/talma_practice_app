<?php

namespace Tests\Feature;

use App\Models\ActivityEvent;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\MatchingGame;
use App\Models\Organization;
use App\Models\Prompt;
use App\Models\User;
use App\Models\Vocabulary;
use App\Models\VoiceSample;
use App\Services\LessonFlowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class GuidedLessonFlowTest extends TestCase
{
    use RefreshDatabase;

    protected Organization $organization;

    protected Course $course;

    protected Lesson $lesson;

    protected LessonFlowService $flowService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->flowService = app(LessonFlowService::class);

        $this->organization = Organization::create([
            'name' => 'Guided Org',
            'slug' => 'guided-org',
            'access_mode' => 'open',
            'is_active' => true,
            'retain_voice_recordings' => true,
        ]);

        $this->course = Course::create([
            'title' => 'Guided Course',
            'slug' => 'guided-course',
            'is_active' => true,
            'guided_mode_enabled' => true,
            'guided_flow' => ['vocabulary', 'prompts', 'matching'],
        ]);

        $this->organization->courses()->attach($this->course->id, ['is_org_wide' => true]);

        Organization::create([
            'name' => 'Default',
            'slug' => 'default',
            'access_mode' => 'open',
            'is_active' => true,
        ])->courses()->attach($this->course->id, ['is_org_wide' => true]);

        $this->lesson = Lesson::create([
            'title' => 'Guided Lesson',
            'slug' => 'guided-lesson',
            'course_id' => $this->course->id,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        Vocabulary::create([
            'lesson_id' => $this->lesson->id,
            'english_word' => 'hello',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        Vocabulary::create([
            'lesson_id' => $this->lesson->id,
            'english_word' => 'goodbye',
            'sort_order' => 2,
            'is_active' => true,
        ]);

        Prompt::create([
            'lesson_id' => $this->lesson->id,
            'prompt_text' => 'Say hello',
            'template' => 'Hello {}.',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        MatchingGame::create([
            'lesson_id' => $this->lesson->id,
            'title' => 'Guided Lesson Matching Game 1',
            'vocabulary_ids' => [],
            'sort_order' => 3,
            'is_active' => true,
        ]);
    }

    public function test_free_choice_lesson_page_when_guided_mode_off(): void
    {
        $this->course->update(['guided_mode_enabled' => false]);

        $response = $this->get(route('lessons.show', $this->lesson->slug));

        $response->assertOk();
        $response->assertDontSee('Start lesson');
        $response->assertDontSee('All activities', false);
    }

    public function test_guided_lesson_page_shows_start_and_escape_hatch(): void
    {
        $response = $this->get(route('lessons.show', $this->lesson->slug));

        $response->assertOk();
        $response->assertSee('Start lesson');
        $response->assertSee('All activities');
        $response->assertSee('id="activities-section"', false);
    }

    public function test_flow_service_expands_steps_and_skips_missing_types(): void
    {
        $this->lesson->load([
            'course',
            'vocabulary',
            'prompts',
            'matchingGames',
            'flashcardGames',
            'spellingGames',
            'clauseExercises',
            'trueFalseGames',
        ]);

        $steps = $this->flowService->steps($this->lesson);

        $this->assertSame(['vocabulary', 'prompts', 'matching'], $steps->pluck('type')->all());
    }

    public function test_resume_returns_first_incomplete_step(): void
    {
        $sessionId = 'test-session-guid';

        ActivityEvent::create([
            'session_id' => $sessionId,
            'lesson_id' => $this->lesson->id,
            'activity_type' => 'vocabulary',
            'activity_id' => $this->lesson->vocabulary()->orderBy('sort_order')->first()->id,
            'status' => 'completed',
        ]);

        $this->lesson->load([
            'course',
            'vocabulary',
            'prompts',
            'matchingGames',
            'flashcardGames',
            'spellingGames',
            'clauseExercises',
            'trueFalseGames',
        ]);

        $resume = $this->flowService->resumeStep($this->lesson, $sessionId);

        $this->assertNotNull($resume);
        $this->assertSame('vocabulary', $resume->type);
    }

    public function test_resume_advances_after_vocabulary_complete(): void
    {
        $sessionId = 'test-session-vocab-done';
        $vocabId = $this->lesson->vocabulary()->orderBy('sort_order')->pluck('id');
        foreach ($vocabId as $id) {
            ActivityEvent::create([
                'session_id' => $sessionId,
                'lesson_id' => $this->lesson->id,
                'activity_type' => 'vocabulary',
                'activity_id' => $id,
                'status' => 'completed',
            ]);
        }

        $this->lesson->load([
            'course',
            'vocabulary',
            'prompts',
            'matchingGames',
            'flashcardGames',
            'spellingGames',
            'clauseExercises',
            'trueFalseGames',
        ]);

        $this->assertTrue($this->flowService->isVocabularyStepComplete($this->lesson, $sessionId));

        $resume = $this->flowService->resumeStep($this->lesson, $sessionId);

        $this->assertSame('prompts', $resume?->type);
    }

    public function test_guided_vocabulary_route_loads(): void
    {
        $response = $this->get(route('guided.vocabulary', $this->lesson));

        $response->assertOk();
        $response->assertSee('hello');
        $response->assertSee('Record yourself saying the word');
    }

    public function test_vocabulary_voice_upload_accepts_vocabulary_id(): void
    {
        config(['app.allow_recording_upload' => true]);
        Storage::fake('voice_training');

        $user = User::create([
            'name' => 'Guided Learner',
            'email' => 'guided@example.com',
            'password' => bcrypt('password123'),
            'role' => 'student',
            'is_active' => true,
            'age' => 10,
            'gender' => 'female',
            'native_language' => 'hebrew',
            'voice_recording_consented_at' => now(),
        ]);

        $this->organization->users()->attach($user->id, ['role' => 'student']);

        $vocab = $this->lesson->vocabulary()->first();
        $wavHeader = "RIFF\x24\x00\x00\x00WAVEfmt \x10\x00\x00\x00\x01\x00\x01\x00\x40\x1f\x00\x00\x80\x3e\x00\x00\x02\x00\x10\x00data\x00\x00\x00\x00";

        $response = $this->actingAs($user, 'admin')->postJson(route('voice-samples.store'), [
            'organization_id' => $this->organization->id,
            'lesson_id' => $this->lesson->id,
            'vocabulary_id' => $vocab->id,
            'generated_sentence' => 'hello',
            'recording' => UploadedFile::fake()->createWithContent('recording.wav', $wavHeader),
        ]);

        $response->assertCreated();

        $sample = VoiceSample::first();
        $this->assertSame($vocab->id, $sample->vocabulary_id);
        $this->assertNull($sample->prompt_id);
        $this->assertNull($sample->option_id);
        $this->assertSame('hello', $sample->target_text);
    }

    public function test_prompts_play_shows_next_step_when_guided(): void
    {
        $response = $this->get(route('prompts.play', $this->lesson));

        $response->assertOk();
        $response->assertSee('Next:');
    }
}
