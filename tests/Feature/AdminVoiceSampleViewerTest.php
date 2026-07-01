<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\Option;
use App\Models\Organization;
use App\Models\Prompt;
use App\Models\User;
use App\Models\VoiceSample;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminVoiceSampleViewerTest extends TestCase
{
    use RefreshDatabase;

    protected Organization $organization;

    protected Lesson $lesson;

    protected VoiceSample $sample;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('voice_training');
        config(['app.voice_sample_viewer_emails' => ['viewer@example.com']]);

        $this->organization = Organization::create([
            'name' => 'Viewer Org',
            'slug' => 'viewer-org',
            'access_mode' => 'restricted',
            'retain_voice_recordings' => true,
            'is_active' => true,
        ]);

        $course = Course::create([
            'title' => 'Viewer Course',
            'slug' => 'viewer-course',
            'is_active' => true,
        ]);

        $this->organization->courses()->attach($course->id, ['is_org_wide' => true]);

        $this->lesson = Lesson::create([
            'title' => 'Viewer Lesson',
            'slug' => 'viewer-lesson',
            'course_id' => $course->id,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $prompt = Prompt::create([
            'lesson_id' => $this->lesson->id,
            'prompt_text' => 'Color?',
            'template' => 'My favorite color is {}.',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $option = Option::create([
            'prompt_id' => $prompt->id,
            'label' => 'red',
            'image_path' => 'images/colors/red.png',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        Storage::disk('voice_training')->put('voice-training/viewer-org/2026/06/sample.mp3', 'fake-audio-bytes');

        $this->sample = VoiceSample::create([
            'organization_id' => $this->organization->id,
            'lesson_id' => $this->lesson->id,
            'prompt_id' => $prompt->id,
            'option_id' => $option->id,
            'target_text' => 'My favorite color is red.',
            'age' => 10,
            'gender' => 'female',
            'native_language' => 'hebrew',
            's3_key' => 'voice-training/viewer-org/2026/06/sample.mp3',
            'metadata_s3_key' => 'voice-training/viewer-org/2026/06/sample.json',
            'duration_ms' => 2500,
            'mime_original' => 'audio/mpeg',
            'recorded_at' => now(),
        ]);
    }

    private function allowlistedAdmin(): User
    {
        return User::create([
            'name' => 'Viewer Admin',
            'email' => 'viewer@example.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'is_active' => true,
        ]);
    }

    private function otherAdmin(): User
    {
        return User::create([
            'name' => 'Other Admin',
            'email' => 'other-admin@example.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'is_active' => true,
        ]);
    }

    private function teacher(): User
    {
        return User::create([
            'name' => 'Teacher User',
            'email' => 'teacher@example.com',
            'password' => bcrypt('password'),
            'role' => 'teacher',
            'is_active' => true,
        ]);
    }

    public function test_guest_is_redirected_to_admin_login(): void
    {
        $this->get(route('admin.voice-samples.index'))
            ->assertRedirect(route('admin.login.show'));
    }

    public function test_non_allowlisted_admin_gets_forbidden(): void
    {
        $this->actingAs($this->otherAdmin(), 'admin')
            ->get(route('admin.voice-samples.index'))
            ->assertForbidden();
    }

    public function test_teacher_gets_forbidden(): void
    {
        $this->actingAs($this->teacher(), 'admin')
            ->get(route('admin.voice-samples.index'))
            ->assertForbidden();
    }

    public function test_allowlisted_admin_can_view_index(): void
    {
        $this->actingAs($this->allowlistedAdmin(), 'admin')
            ->get(route('admin.voice-samples.index'))
            ->assertOk()
            ->assertSee('Voice Training Samples')
            ->assertSee('My favorite color is red.');
    }

    public function test_allowlisted_admin_can_stream_local_audio(): void
    {
        $response = $this->actingAs($this->allowlistedAdmin(), 'admin')
            ->get(route('admin.voice-samples.audio', $this->sample));

        $response->assertOk();
        $this->assertStringContainsString('fake-audio-bytes', $response->streamedContent());
    }

    public function test_non_allowlisted_admin_cannot_stream_audio(): void
    {
        $this->actingAs($this->otherAdmin(), 'admin')
            ->get(route('admin.voice-samples.audio', $this->sample))
            ->assertForbidden();
    }

    public function test_index_filters_by_target_text_search(): void
    {
        VoiceSample::create([
            'organization_id' => $this->organization->id,
            'lesson_id' => $this->lesson->id,
            'prompt_id' => $this->sample->prompt_id,
            'option_id' => $this->sample->option_id,
            'target_text' => 'Something completely different.',
            'age' => 12,
            'gender' => 'male',
            'native_language' => 'arabic',
            's3_key' => 'voice-training/viewer-org/2026/06/other.mp3',
            'recorded_at' => now(),
        ]);

        $this->actingAs($this->allowlistedAdmin(), 'admin')
            ->get(route('admin.voice-samples.index', ['search' => 'favorite color']))
            ->assertOk()
            ->assertSee('My favorite color is red.')
            ->assertDontSee('Something completely different.');
    }
}
