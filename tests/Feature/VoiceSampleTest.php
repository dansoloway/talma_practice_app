<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\Option;
use App\Models\Organization;
use App\Models\ParentStudent;
use App\Models\Prompt;
use App\Models\User;
use App\Models\VoiceSample;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class VoiceSampleTest extends TestCase
{
    use RefreshDatabase;

    protected Organization $organization;

    protected Course $course;

    protected Lesson $lesson;

    protected Prompt $prompt;

    protected Option $option;

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.allow_recording_upload' => true]);
        Storage::fake('voice_training');

        $this->organization = Organization::create([
            'name' => 'Voice Test Org',
            'slug' => 'voice-test-org',
            'access_mode' => 'restricted',
            'allow_self_registration' => true,
            'retain_voice_recordings' => true,
            'is_active' => true,
        ]);

        $this->course = Course::create([
            'title' => 'Voice Course',
            'slug' => 'voice-course',
            'is_active' => true,
        ]);

        $this->organization->courses()->attach($this->course->id, ['is_org_wide' => true]);

        $this->lesson = Lesson::create([
            'title' => 'Voice Lesson',
            'slug' => 'voice-lesson',
            'course_id' => $this->course->id,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $this->prompt = Prompt::create([
            'lesson_id' => $this->lesson->id,
            'prompt_text' => 'What is your favorite color?',
            'template' => 'My favorite color is {}.',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $this->option = Option::create([
            'prompt_id' => $this->prompt->id,
            'label' => 'red',
            'image_path' => 'images/colors/red.png',
            'is_active' => true,
            'sort_order' => 1,
        ]);
    }

    private function createConsentedStudent(): User
    {
        $user = User::create([
            'name' => 'Voice Learner',
            'email' => 'voice-learner@example.com',
            'password' => bcrypt('password123'),
            'role' => 'student',
            'is_active' => true,
            'age' => 11,
            'gender' => 'female',
            'native_language' => 'hebrew',
            'voice_recording_consented_at' => now(),
        ]);

        $this->organization->users()->attach($user->id, ['role' => 'student']);

        return $user;
    }

    private function fakeRecording(): UploadedFile
    {
        $wavHeader = "RIFF\x24\x00\x00\x00WAVEfmt \x10\x00\x00\x00\x01\x00\x01\x00\x40\x1f\x00\x00\x80\x3e\x00\x00\x02\x00\x10\x00data\x00\x00\x00\x00";
        $content = $wavHeader.str_repeat("\0", 3000);

        return UploadedFile::fake()->createWithContent('recording.wav', $content);
    }

    public function test_registration_requires_age_gender_and_consent_when_org_collects_voice(): void
    {
        $response = $this->post(route('org.student.register.submit', $this->organization), [
            'name' => 'New Learner',
            'email' => 'new@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertSessionHasErrors(['age', 'gender', 'native_language', 'voice_recording_consent']);
    }

    public function test_registration_stores_age_gender_and_consent(): void
    {
        $response = $this->post(route('org.student.register.submit', $this->organization), [
            'name' => 'New Learner',
            'email' => 'new@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'age' => 12,
            'gender' => 'male',
            'native_language' => 'arabic',
            'voice_recording_consent' => '1',
            'terms_accepted' => '1',
        ]);

        $response->assertRedirect(route('org.student.index', $this->organization));

        $user = User::where('email', 'new@example.com')->first();
        $this->assertSame(12, $user->age);
        $this->assertSame('male', $user->gender);
        $this->assertSame('arabic', $user->native_language);
        $this->assertNotNull($user->voice_recording_consented_at);
    }

    public function test_voice_sample_upload_is_blocked_without_consent(): void
    {
        $user = User::create([
            'name' => 'No Consent',
            'email' => 'nonsent@example.com',
            'password' => bcrypt('password123'),
            'role' => 'student',
            'is_active' => true,
        ]);
        $this->organization->users()->attach($user->id, ['role' => 'student']);

        $response = $this->actingAs($user, 'admin')->postJson(route('voice-samples.store'), [
            'organization_id' => $this->organization->id,
            'lesson_id' => $this->lesson->id,
            'prompt_id' => $this->prompt->id,
            'option_id' => $this->option->id,
            'generated_sentence' => 'My favorite color is red.',
            'recording' => $this->fakeRecording(),
        ]);

        $response->assertForbidden();
        $this->assertDatabaseCount('voice_samples', 0);
    }

    public function test_voice_sample_upload_is_blocked_when_org_toggle_off(): void
    {
        $this->organization->update(['retain_voice_recordings' => false]);
        $user = $this->createConsentedStudent();

        $response = $this->actingAs($user, 'admin')->postJson(route('voice-samples.store'), [
            'organization_id' => $this->organization->id,
            'lesson_id' => $this->lesson->id,
            'prompt_id' => $this->prompt->id,
            'option_id' => $this->option->id,
            'generated_sentence' => 'My favorite color is red.',
            'recording' => $this->fakeRecording(),
        ]);

        $response->assertForbidden();
    }

    public function test_voice_sample_upload_works_when_org_collects_recordings_without_global_flag(): void
    {
        config(['app.allow_recording_upload' => false]);
        $user = $this->createConsentedStudent();

        $response = $this->actingAs($user, 'admin')->postJson(route('voice-samples.store'), [
            'organization_id' => $this->organization->id,
            'lesson_id' => $this->lesson->id,
            'prompt_id' => $this->prompt->id,
            'option_id' => $this->option->id,
            'generated_sentence' => 'My favorite color is red.',
            'recording' => $this->fakeRecording(),
        ]);

        $response->assertCreated();
    }

    public function test_voice_sample_upload_stores_anonymized_metadata(): void
    {
        $user = $this->createConsentedStudent();

        $response = $this->actingAs($user, 'admin')->postJson(route('voice-samples.store'), [
            'organization_id' => $this->organization->id,
            'lesson_id' => $this->lesson->id,
            'prompt_id' => $this->prompt->id,
            'option_id' => $this->option->id,
            'generated_sentence' => 'My favorite color is red.',
            'duration_ms' => 3500,
            'recording' => $this->fakeRecording(),
        ]);

        $response->assertCreated();
        $response->assertJson(['success' => true]);

        $sample = VoiceSample::first();
        $this->assertNotNull($sample);
        $this->assertSame('My favorite color is red.', $sample->target_text);
        $this->assertSame(11, $sample->age);
        $this->assertSame('female', $sample->gender);
        $this->assertSame('hebrew', $sample->native_language);
        $this->assertFalse(Schema::hasColumn('voice_samples', 'user_id'));

        Storage::disk('voice_training')->assertExists($sample->s3_key);
        Storage::disk('voice_training')->assertExists($sample->metadata_s3_key);

        $metadata = json_decode(Storage::disk('voice_training')->get($sample->metadata_s3_key), true);
        $this->assertSame('My favorite color is red.', $metadata['target_text']);
        $this->assertSame(11, $metadata['age']);
        $this->assertSame('female', $metadata['gender']);
        $this->assertSame('hebrew', $metadata['native_language']);
        $this->assertArrayNotHasKey('user_id', $metadata);
    }

    public function test_voice_sample_upload_rejects_empty_recording(): void
    {
        $user = $this->createConsentedStudent();
        $tiny = UploadedFile::fake()->createWithContent('recording.webm', str_repeat("\0", 100));

        $response = $this->actingAs($user, 'admin')->postJson(route('voice-samples.store'), [
            'organization_id' => $this->organization->id,
            'lesson_id' => $this->lesson->id,
            'prompt_id' => $this->prompt->id,
            'option_id' => $this->option->id,
            'generated_sentence' => 'My favorite color is red.',
            'recording' => $tiny,
        ]);

        $response->assertStatus(422);
        $response->assertJson(['error' => 'Recording was too short or empty. Please try again.']);
        $this->assertSame(0, VoiceSample::count());
    }

    public function test_parent_upload_uses_selected_child_profile(): void
    {
        $parent = User::create([
            'name' => 'Parent User',
            'email' => 'parent-voice@example.com',
            'password' => bcrypt('password123'),
            'role' => User::ROLE_PARENT,
            'is_active' => true,
            'voice_recording_consented_at' => now(),
        ]);

        $this->organization->users()->attach($parent->id, ['role' => 'parent']);

        $child = ParentStudent::create([
            'parent_id' => $parent->id,
            'first_name' => 'Kid',
            'last_name' => 'One',
            'first_name_english' => 'Kid',
            'last_name_english' => 'One',
            'birth_date' => now()->subYears(10)->format('Y-m-d'),
            'grade' => 4,
            'gender' => 'male',
            'native_language' => 'arabic',
        ]);

        $response = $this->actingAs($parent, 'admin')
            ->withSession(['selected_student_id' => $child->id])
            ->postJson(route('voice-samples.store'), [
                'organization_id' => $this->organization->id,
                'lesson_id' => $this->lesson->id,
                'prompt_id' => $this->prompt->id,
                'option_id' => $this->option->id,
                'generated_sentence' => 'My favorite color is red.',
                'recording' => $this->fakeRecording(),
            ]);

        $response->assertCreated();

        $sample = VoiceSample::first();
        $this->assertSame(10, $sample->age);
        $this->assertSame('male', $sample->gender);
        $this->assertSame('arabic', $sample->native_language);
    }
}
