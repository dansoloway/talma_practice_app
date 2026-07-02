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
use App\Support\PracticeLearnerScope;
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
        $response->assertSee('Start: Learn the Words');
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
        $user = User::create([
            'name' => 'Guided Guest Learner',
            'email' => 'guided-guest@example.com',
            'password' => bcrypt('password123'),
            'role' => 'student',
            'is_active' => true,
            'age' => 10,
            'gender' => 'female',
            'native_language' => 'hebrew',
            'voice_recording_consented_at' => now(),
        ]);

        $response = $this->actingAs($user, 'admin')->get(route('guided.vocabulary', $this->lesson));

        $response->assertOk();
        $response->assertSee('hello');
        $response->assertSee('Tap to say the word');
        $response->assertSee('id="speech-check-btn"', false);
        $this->assertMatchesRegularExpression('/id="next-word-btn"[^>]*class="[^"]*\bhidden\b/', $response->getContent());
    }

    public function test_org_scoped_guided_vocabulary_route_loads_for_authenticated_member(): void
    {
        $user = User::create([
            'name' => 'Summer Student',
            'email' => 'summer-student@example.com',
            'password' => bcrypt('password123'),
            'role' => 'student',
            'is_active' => true,
            'age' => 10,
            'gender' => 'female',
            'native_language' => 'hebrew',
            'voice_recording_consented_at' => now(),
        ]);

        $this->organization->update(['retain_voice_recordings' => true]);
        $this->organization->users()->attach($user->id, ['role' => 'student']);

        $response = $this->actingAs($user, 'admin')->get(
            route('org.student.guided.vocabulary', [
                'organization' => $this->organization,
                'lesson' => $this->lesson,
            ])
        );

        $response->assertOk();
        $response->assertSee('hello');
        $response->assertSee('Tap to say the word');
        $response->assertSee('id="speech-check-btn"', false);
    }

    public function test_guided_vocabulary_advances_after_first_word_marked_complete(): void
    {
        $sessionId = 'vocab-advance-session';
        $firstWord = $this->lesson->vocabulary()->orderBy('sort_order')->first();

        $user = User::create([
            'name' => 'Summer Student',
            'email' => 'vocab-advance@example.com',
            'password' => bcrypt('password123'),
            'role' => 'student',
            'is_active' => true,
            'age' => 10,
            'gender' => 'female',
            'native_language' => 'hebrew',
            'voice_recording_consented_at' => now(),
        ]);

        $this->organization->users()->attach($user->id, ['role' => 'student']);

        ActivityEvent::create([
            'session_id' => PracticeLearnerScope::forUser($user, $sessionId),
            'lesson_id' => $this->lesson->id,
            'activity_type' => 'vocabulary',
            'activity_id' => $firstWord->id,
            'status' => 'completed',
        ]);

        $response = $this->actingAs($user, 'admin')
            ->withCookie('talma_session_id', $sessionId)
            ->get(route('org.student.guided.vocabulary', [
                'organization' => $this->organization,
                'lesson' => $this->lesson,
            ]));

        $response->assertOk();
        $response->assertSee('goodbye');
        $response->assertSee('Word 2 of 2');
        $response->assertDontSee('Word 1 of 2');
    }

    public function test_guided_vocabulary_can_open_specific_word_and_go_back(): void
    {
        $user = User::create([
            'name' => 'Summer Student',
            'email' => 'vocab-nav@example.com',
            'password' => bcrypt('password123'),
            'role' => 'student',
            'is_active' => true,
            'age' => 10,
            'gender' => 'female',
            'native_language' => 'hebrew',
            'voice_recording_consented_at' => now(),
        ]);

        $this->organization->users()->attach($user->id, ['role' => 'student']);

        $secondWordUrl = route('org.student.guided.vocabulary', [
            'organization' => $this->organization,
            'lesson' => $this->lesson,
            'word' => 2,
        ]);

        $response = $this->actingAs($user, 'admin')->get($secondWordUrl);

        $response->assertOk();
        $response->assertSee('goodbye');
        $response->assertSee('Word 2 of 2');
        $response->assertSee('Previous word', false);
        $response->assertSee('?word=1', false);

        $firstWordResponse = $this->actingAs($user, 'admin')->get(route('org.student.guided.vocabulary', [
            'organization' => $this->organization,
            'lesson' => $this->lesson,
            'word' => 1,
        ]));

        $firstWordResponse->assertOk();
        $firstWordResponse->assertSee('hello');
        $firstWordResponse->assertDontSee('Previous word', false);
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

    public function test_voice_sample_accepts_recording_source(): void
    {
        config(['app.allow_recording_upload' => true]);
        Storage::fake('voice_training');

        $user = User::create([
            'name' => 'Guided Learner',
            'email' => 'guided-source@example.com',
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
            'recording_source' => 'pronunciation_check',
            'recording' => UploadedFile::fake()->createWithContent('recording.wav', $wavHeader),
        ]);

        $response->assertCreated();

        $sample = VoiceSample::first();
        $metadata = json_decode(Storage::disk('voice_training')->get($sample->metadata_s3_key), true);
        $this->assertSame('pronunciation_check', $metadata['recording_source']);
    }

    public function test_visited_and_learned_vocabulary_ids_distinguish_pass_and_fail(): void
    {
        $sessionId = 'visited-learned-session';
        $words = $this->lesson->vocabulary()->orderBy('sort_order')->get();

        ActivityEvent::create([
            'session_id' => $sessionId,
            'lesson_id' => $this->lesson->id,
            'activity_type' => 'vocabulary',
            'activity_id' => $words[0]->id,
            'status' => 'completed',
            'meta' => [
                'pronunciation_pass' => true,
                'source' => 'pronunciation_check',
            ],
        ]);

        ActivityEvent::create([
            'session_id' => $sessionId,
            'lesson_id' => $this->lesson->id,
            'activity_type' => 'vocabulary',
            'activity_id' => $words[1]->id,
            'status' => 'completed',
            'meta' => [
                'pronunciation_pass' => false,
                'source' => 'pronunciation_check',
            ],
        ]);

        $this->lesson->load('vocabulary');

        $visited = $this->flowService->visitedVocabularyIds($this->lesson, $sessionId);
        $learned = $this->flowService->learnedVocabularyIds($this->lesson, $sessionId);

        $this->assertCount(2, $visited);
        $this->assertCount(1, $learned);
        $this->assertTrue($learned->contains($words[0]->id));
        $this->assertFalse($learned->contains($words[1]->id));
    }

    public function test_first_incomplete_vocabulary_advances_after_failed_visit(): void
    {
        $sessionId = 'failed-visit-session';
        $words = $this->lesson->vocabulary()->orderBy('sort_order')->get();

        ActivityEvent::create([
            'session_id' => $sessionId,
            'lesson_id' => $this->lesson->id,
            'activity_type' => 'vocabulary',
            'activity_id' => $words[0]->id,
            'status' => 'completed',
            'meta' => [
                'pronunciation_pass' => false,
                'source' => 'pronunciation_check',
            ],
        ]);

        $this->lesson->load('vocabulary');

        $nextWord = $this->flowService->firstIncompleteVocabulary($this->lesson, $sessionId);

        $this->assertNotNull($nextWord);
        $this->assertSame($words[1]->id, $nextWord->id);
    }

    public function test_legacy_vocabulary_events_count_as_learned(): void
    {
        $sessionId = 'legacy-vocab-session';
        $word = $this->lesson->vocabulary()->orderBy('sort_order')->first();

        ActivityEvent::create([
            'session_id' => $sessionId,
            'lesson_id' => $this->lesson->id,
            'activity_type' => 'vocabulary',
            'activity_id' => $word->id,
            'status' => 'completed',
        ]);

        $this->lesson->load('vocabulary');

        $learned = $this->flowService->learnedVocabularyIds($this->lesson, $sessionId);

        $this->assertTrue($learned->contains($word->id));
    }

    public function test_vocabulary_step_complete_when_all_words_visited_even_if_not_all_learned(): void
    {
        $sessionId = 'mixed-vocab-session';
        $words = $this->lesson->vocabulary()->orderBy('sort_order')->get();

        foreach ($words as $word) {
            ActivityEvent::create([
                'session_id' => $sessionId,
                'lesson_id' => $this->lesson->id,
                'activity_type' => 'vocabulary',
                'activity_id' => $word->id,
                'status' => 'completed',
                'meta' => [
                    'pronunciation_pass' => $word->id === $words[0]->id,
                    'source' => 'pronunciation_check',
                ],
            ]);
        }

        $this->lesson->load('vocabulary');

        $this->assertTrue($this->flowService->isVocabularyStepComplete($this->lesson, $sessionId));
        $this->assertCount(1, $this->flowService->learnedVocabularyIds($this->lesson, $sessionId));
    }

    public function test_vocabulary_progress_summary_counts_learned_and_needs_practice(): void
    {
        $sessionId = 'vocab-progress-summary';
        $words = $this->lesson->vocabulary()->orderBy('sort_order')->get();

        ActivityEvent::create([
            'session_id' => $sessionId,
            'lesson_id' => $this->lesson->id,
            'activity_type' => 'vocabulary',
            'activity_id' => $words[0]->id,
            'status' => 'completed',
            'meta' => ['pronunciation_pass' => true, 'source' => 'pronunciation_check'],
        ]);

        ActivityEvent::create([
            'session_id' => $sessionId,
            'lesson_id' => $this->lesson->id,
            'activity_type' => 'vocabulary',
            'activity_id' => $words[1]->id,
            'status' => 'completed',
            'meta' => ['pronunciation_pass' => false, 'source' => 'pronunciation_check'],
        ]);

        $this->lesson->load('vocabulary');

        $summary = $this->flowService->vocabularyProgressSummary($this->lesson, $sessionId);

        $this->assertSame(2, $summary['total']);
        $this->assertSame(1, $summary['learned']);
        $this->assertSame(2, $summary['visited']);
        $this->assertSame('learned', $summary['statuses'][$words[0]->id]);
        $this->assertSame('needs_practice', $summary['statuses'][$words[1]->id]);
    }

    public function test_guided_vocabulary_shows_word_progress_strip(): void
    {
        $sessionId = 'guided-vocab-progress-strip';
        $words = $this->lesson->vocabulary()->orderBy('sort_order')->get();

        $user = User::create([
            'name' => 'Progress Student',
            'email' => 'progress-strip@example.com',
            'password' => bcrypt('password123'),
            'role' => 'student',
            'is_active' => true,
            'age' => 10,
            'gender' => 'female',
            'native_language' => 'hebrew',
            'voice_recording_consented_at' => now(),
        ]);

        $this->organization->users()->attach($user->id, ['role' => 'student']);

        ActivityEvent::create([
            'session_id' => PracticeLearnerScope::forUser($user, $sessionId),
            'lesson_id' => $this->lesson->id,
            'activity_type' => 'vocabulary',
            'activity_id' => $words[0]->id,
            'status' => 'completed',
            'meta' => ['pronunciation_pass' => true, 'source' => 'pronunciation_check'],
        ]);

        $response = $this->actingAs($user, 'admin')
            ->withCookie('talma_session_id', $sessionId)
            ->get(route('org.student.guided.vocabulary', [
                'organization' => $this->organization,
                'lesson' => $this->lesson,
            ]));

        $response->assertOk();
        $response->assertSee('id="vocab-progress-summary"', false);
        $response->assertSee('vocab-progress-bar', false);
        $response->assertSee('vocab-learned-count', false);
        $response->assertSee('/ 2 mastered', false);
        $response->assertSee($words[1]->english_word);
    }

    public function test_lesson_page_shows_vocabulary_word_progress(): void
    {
        $sessionId = 'lesson-vocab-progress';
        $word = $this->lesson->vocabulary()->orderBy('sort_order')->first();

        ActivityEvent::create([
            'session_id' => $sessionId,
            'lesson_id' => $this->lesson->id,
            'activity_type' => 'vocabulary',
            'activity_id' => $word->id,
            'status' => 'completed',
            'meta' => ['pronunciation_pass' => true, 'source' => 'pronunciation_check'],
        ]);

        $response = $this->withCookie('talma_session_id', $sessionId)
            ->get(route('lessons.show', $this->lesson->slug));

        $response->assertOk();
        $response->assertSee('lesson-vocabulary-preview', false);
        $response->assertSee('of 2 words mastered', false);
        $response->assertSee('Got it');
    }

    public function test_prompts_play_shows_next_step_when_guided(): void
    {
        $response = $this->get(route('prompts.play', $this->lesson));

        $response->assertOk();
        $response->assertSee('Next:');
    }

    public function test_lesson_page_shows_completion_when_all_guided_steps_done(): void
    {
        $sessionId = 'complete-session-guid-001';
        $this->completeAllGuidedSteps($sessionId);

        $response = $this->withCookie('talma_session_id', $sessionId)
            ->get(route('lessons.show', $this->lesson->slug));

        $response->assertOk();
        $response->assertSee('You finished this lesson!');
        $response->assertDontSee('Start lesson');
        $response->assertSee('Review activities');
        $response->assertSee('Complete · 100%');
    }

    public function test_course_page_lesson_card_shows_complete_at_100_percent(): void
    {
        $sessionId = 'complete-session-guid-002';
        $this->completeAllGuidedSteps($sessionId);

        $response = $this->withCookie('talma_session_id', $sessionId)
            ->get(route('student.course', $this->course->slug));

        $response->assertOk();
        $response->assertSee('Complete');
    }

    public function test_is_lesson_complete_for_free_choice_when_all_activities_done(): void
    {
        $this->course->update(['guided_mode_enabled' => false]);

        $sessionId = 'free-choice-complete';
        $matchingId = $this->lesson->matchingGames()->first()->id;

        ActivityEvent::create([
            'session_id' => $sessionId,
            'lesson_id' => $this->lesson->id,
            'activity_type' => 'prompts',
            'activity_id' => null,
            'status' => 'completed',
        ]);

        ActivityEvent::create([
            'session_id' => $sessionId,
            'lesson_id' => $this->lesson->id,
            'activity_type' => 'matching',
            'activity_id' => $matchingId,
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

        $this->assertTrue($this->flowService->isLessonComplete($this->lesson, $sessionId));
    }

    public function test_guided_vocabulary_loads_speech_tools_after_talma_speech_script(): void
    {
        config(['app.speech_feedback_enabled' => true]);

        $response = $this->get(route('guided.vocabulary', $this->lesson));

        $response->assertOk();
        $response->assertSee('Tap to say the word');
        $response->assertSee('id="speech-check-btn"', false);

        $html = $response->getContent();
        $speechScriptPos = strpos($html, 'talma-speech.js');
        $initPos = strpos($html, 'initSpeechFeedback');

        $this->assertNotFalse($speechScriptPos);
        $this->assertNotFalse($initPos);
        $this->assertLessThan($initPos, $speechScriptPos);
        $this->assertStringContainsString('recordAudio: voiceUploadConfig.enabled', $html);
    }

    public function test_guided_vocabulary_exposes_release_microphone_helper(): void
    {
        $response = $this->get(route('guided.vocabulary', $this->lesson));

        $response->assertOk();
        $response->assertSee('TalmaSpeech.releaseMicrophoneAccess', false);
    }

    private function completeAllGuidedSteps(string $sessionId): void
    {
        foreach ($this->lesson->vocabulary()->orderBy('sort_order')->pluck('id') as $vocabId) {
            ActivityEvent::create([
                'session_id' => $sessionId,
                'lesson_id' => $this->lesson->id,
                'activity_type' => 'vocabulary',
                'activity_id' => $vocabId,
                'status' => 'completed',
            ]);
        }

        ActivityEvent::create([
            'session_id' => $sessionId,
            'lesson_id' => $this->lesson->id,
            'activity_type' => 'prompts',
            'activity_id' => null,
            'status' => 'completed',
        ]);

        $matchingId = $this->lesson->matchingGames()->first()->id;

        ActivityEvent::create([
            'session_id' => $sessionId,
            'lesson_id' => $this->lesson->id,
            'activity_type' => 'matching',
            'activity_id' => $matchingId,
            'status' => 'completed',
        ]);
    }
}
