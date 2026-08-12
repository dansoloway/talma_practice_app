<?php

namespace Tests\Feature;

use App\Mail\SummerDailyUsageReportMail;
use App\Models\ActivityEvent;
use App\Models\Course;
use App\Models\LearnerLoginEvent;
use App\Models\Lesson;
use App\Models\Organization;
use App\Models\Prompt;
use App\Models\User;
use App\Models\VoiceSample;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class SummerDailyUsageReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_emails_yesterday_counts_for_jerusalem_day(): void
    {
        Mail::fake();
        config(['app.summer_daily_report_emails' => ['daniel@talmaisrael.com']]);

        $org = Organization::create([
            'name' => 'TALMA Summer',
            'slug' => Organization::SUMMER_PRACTICE_PAL_SLUG,
            'access_mode' => 'restricted',
            'allow_self_registration' => true,
            'registration_type' => Organization::REGISTRATION_TYPE_PARENT_SIGNUP,
            'retain_voice_recordings' => true,
            'is_active' => true,
        ]);

        $course = Course::create([
            'title' => 'TALMA Summer — Pre-A1',
            'slug' => 'summer-practice-pal-pre-a1',
            'is_active' => true,
            'guided_mode_enabled' => true,
            'guided_flow' => ['prompts'],
        ]);
        $org->courses()->attach($course->id, ['is_org_wide' => true]);

        $lesson = Lesson::create([
            'title' => 'Day 1',
            'slug' => 'summer-practice-pal-pre-a1-day-1',
            'course_id' => $course->id,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        Prompt::create([
            'lesson_id' => $lesson->id,
            'prompt_text' => 'Say hello',
            'template' => 'Hello {}.',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $user = User::factory()->create([
            'role' => User::ROLE_PARENT,
            'is_active' => true,
        ]);

        $duringDay = Carbon::parse('2026-07-20 15:00:00', 'Asia/Jerusalem')->utc();
        $beforeDay = Carbon::parse('2026-07-19 23:00:00', 'Asia/Jerusalem')->utc();
        $afterDay = Carbon::parse('2026-07-21 01:00:00', 'Asia/Jerusalem')->utc();

        LearnerLoginEvent::create([
            'organization_id' => $org->id,
            'user_id' => $user->id,
            'parent_student_id' => null,
            'ip_address' => '127.0.0.1',
            'created_at' => $duringDay,
        ]);
        LearnerLoginEvent::create([
            'organization_id' => $org->id,
            'user_id' => $user->id,
            'parent_student_id' => null,
            'ip_address' => '127.0.0.1',
            'created_at' => $duringDay->copy()->addHour(),
        ]);
        LearnerLoginEvent::create([
            'organization_id' => $org->id,
            'user_id' => $user->id,
            'parent_student_id' => null,
            'ip_address' => '127.0.0.1',
            'created_at' => $beforeDay,
        ]);

        $this->createCompletedActivity('child:1', $lesson->id, $duringDay);

        // Already complete before the window — should not count as completed on report day.
        $this->createCompletedActivity('child:2', $lesson->id, $beforeDay);
        $this->createCompletedActivity('child:2', $lesson->id, $duringDay);

        VoiceSample::create([
            'organization_id' => $org->id,
            'lesson_id' => $lesson->id,
            'prompt_id' => null,
            'option_id' => null,
            'target_text' => 'hello',
            'age' => 10,
            'gender' => 'female',
            'native_language' => 'hebrew',
            's3_key' => 'voice-training/summer/sample.mp3',
            'duration_ms' => 1200,
            'mime_original' => 'audio/mpeg',
            'recording_source' => 'pronunciation_check',
            'recorded_at' => $duringDay,
        ]);
        VoiceSample::create([
            'organization_id' => $org->id,
            'lesson_id' => $lesson->id,
            'prompt_id' => null,
            'option_id' => null,
            'target_text' => 'hello',
            'age' => 10,
            'gender' => 'male',
            'native_language' => 'hebrew',
            's3_key' => 'voice-training/summer/sample-old.mp3',
            'duration_ms' => 1200,
            'mime_original' => 'audio/mpeg',
            'recording_source' => 'manual_record',
            'recorded_at' => $afterDay,
        ]);

        $exit = Artisan::call('talma:summer-daily-usage-report', [
            '--date' => '2026-07-20',
        ]);

        $this->assertSame(0, $exit, Artisan::output());

        Mail::assertSent(SummerDailyUsageReportMail::class, function (SummerDailyUsageReportMail $mail) {
            return $mail->hasTo('daniel@talmaisrael.com')
                && $mail->report['date'] === '2026-07-20'
                && $mail->report['timezone'] === 'Asia/Jerusalem'
                && $mail->report['logins'] === 2
                && $mail->report['lessons_completed'] === 1
                && $mail->report['voice_recordings'] === 1;
        });
    }

    public function test_dry_run_does_not_send_email(): void
    {
        Mail::fake();

        Organization::create([
            'name' => 'TALMA Summer',
            'slug' => Organization::SUMMER_PRACTICE_PAL_SLUG,
            'access_mode' => 'restricted',
            'is_active' => true,
        ]);

        $exit = Artisan::call('talma:summer-daily-usage-report', [
            '--date' => '2026-07-20',
            '--dry-run' => true,
        ]);

        $this->assertSame(0, $exit);
        Mail::assertNothingSent();
    }

    private function createCompletedActivity(string $sessionId, int $lessonId, Carbon $at): void
    {
        $event = new ActivityEvent([
            'session_id' => $sessionId,
            'lesson_id' => $lessonId,
            'activity_type' => 'prompts',
            'activity_id' => null,
            'status' => 'completed',
        ]);
        $event->created_at = $at;
        $event->updated_at = $at;
        $event->save();
    }
}
