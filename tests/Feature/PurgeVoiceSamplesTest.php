<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\Organization;
use App\Models\VoiceSample;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PurgeVoiceSamplesTest extends TestCase
{
    use RefreshDatabase;

    protected Organization $organization;

    protected Lesson $lesson;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('voice_training');

        $this->organization = Organization::create([
            'name' => 'Purge Org',
            'slug' => 'purge-org',
            'access_mode' => 'restricted',
            'retain_voice_recordings' => true,
            'is_active' => true,
        ]);

        $course = Course::create([
            'title' => 'Purge Course',
            'slug' => 'purge-course',
            'is_active' => true,
        ]);

        $this->organization->courses()->attach($course->id, ['is_org_wide' => true]);

        $this->lesson = Lesson::create([
            'title' => 'Purge Lesson',
            'slug' => 'purge-lesson',
            'course_id' => $course->id,
            'is_active' => true,
            'sort_order' => 1,
        ]);
    }

    protected function makeSample(string $key, string $recordedAt): VoiceSample
    {
        Storage::disk('voice_training')->put($key, 'audio');
        Storage::disk('voice_training')->put(str_replace('.mp3', '.json', $key), '{}');

        return VoiceSample::create([
            'organization_id' => $this->organization->id,
            'lesson_id' => $this->lesson->id,
            'target_text' => 'hello',
            'age' => 10,
            'gender' => 'female',
            'native_language' => 'hebrew',
            's3_key' => $key,
            'metadata_s3_key' => str_replace('.mp3', '.json', $key),
            'recording_source' => 'manual_record',
            'recorded_at' => $recordedAt,
        ]);
    }

    public function test_requires_before_option(): void
    {
        $this->artisan('voice-samples:purge')
            ->assertFailed();
    }

    public function test_dry_run_does_not_delete(): void
    {
        $old = $this->makeSample('voice-training/purge-org/2026/07/old.mp3', '2026-07-18 12:00:00');
        $keep = $this->makeSample('voice-training/purge-org/2026/07/keep.mp3', '2026-07-19 00:00:00');

        $this->artisan('voice-samples:purge', [
            '--before' => '2026-07-19',
            '--dry-run' => true,
        ])
            ->expectsOutputToContain('Dry run: 1 voice sample(s) would be deleted')
            ->assertSuccessful();

        $this->assertDatabaseHas('voice_samples', ['id' => $old->id]);
        $this->assertDatabaseHas('voice_samples', ['id' => $keep->id]);
        Storage::disk('voice_training')->assertExists($old->s3_key);
    }

    public function test_purge_deletes_only_samples_before_cutoff(): void
    {
        $old = $this->makeSample('voice-training/purge-org/2026/07/old.mp3', '2026-07-18 23:59:59');
        $onCutoff = $this->makeSample('voice-training/purge-org/2026/07/on-cutoff.mp3', '2026-07-19 00:00:00');
        $newer = $this->makeSample('voice-training/purge-org/2026/07/newer.mp3', '2026-07-20 10:00:00');

        $this->artisan('voice-samples:purge', [
            '--before' => '2026-07-19',
        ])
            ->expectsOutputToContain('Deleted 1 voice sample(s).')
            ->assertSuccessful();

        $this->assertDatabaseMissing('voice_samples', ['id' => $old->id]);
        $this->assertDatabaseHas('voice_samples', ['id' => $onCutoff->id]);
        $this->assertDatabaseHas('voice_samples', ['id' => $newer->id]);

        Storage::disk('voice_training')->assertMissing($old->s3_key);
        Storage::disk('voice_training')->assertMissing($old->metadata_s3_key);
        Storage::disk('voice_training')->assertExists($onCutoff->s3_key);
        Storage::disk('voice_training')->assertExists($newer->s3_key);
    }
}
