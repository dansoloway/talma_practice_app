<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\LearnerLoginEvent;
use App\Models\LearnerVisit;
use App\Models\Lesson;
use App\Models\Organization;
use App\Models\ParentStudent;
use App\Models\User;
use App\Services\LearnerVisitTracker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SummerAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\OrganizationSeeder::class);
        $this->seed(\Database\Seeders\RootOrganizationSeeder::class);
        $this->seed(\Database\Seeders\TermsAndConditionsSeeder::class);
    }

    protected function summerOrg(array $overrides = []): Organization
    {
        return Organization::create(array_merge([
            'name' => 'TALMA Summer',
            'slug' => Organization::SUMMER_PRACTICE_PAL_SLUG,
            'access_mode' => 'restricted',
            'allow_self_registration' => true,
            'registration_type' => Organization::REGISTRATION_TYPE_PARENT_SIGNUP,
            'is_active' => true,
        ], $overrides));
    }

    protected function registerParent(Organization $org, string $email = 'parent@example.com', int $children = 2): User
    {
        $students = [];
        for ($i = 1; $i <= $children; $i++) {
            $students[] = [
                'first_name' => "ילד{$i}",
                'last_name' => 'למד',
                'first_name_english' => "Child{$i}",
                'last_name_english' => 'Learner',
                'birth_year' => '2015',
                'grade' => '4',
                'gender' => 'male',
                'native_language' => 'hebrew',
                'login_type' => 'shared',
            ];
        }

        $this->post(route('org.student.register.submit', $org), [
            'name' => 'Jane Parent',
            'hebrew_name' => 'ג׳יין הורה',
            'id_number' => (string) random_int(100000000, 999999999),
            'email' => $email,
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'phone_prefix' => '050',
            'phone_rest' => (string) random_int(1000000, 9999999),
            'terms_accepted' => '1',
            'privacy_policy_read' => '1',
            'students' => $students,
        ])->assertRedirect();

        return User::where('email', $email)->firstOrFail();
    }

    public function test_parent_signup_appears_on_analytics_with_child_count(): void
    {
        $org = $this->summerOrg();
        $this->registerParent($org, 'signup@example.com', 2);

        $admin = User::factory()->admin()->create();
        $response = $this->actingAs($admin, 'admin')
            ->get(route('org.admin.summer-analytics', $org));

        $response->assertOk()
            ->assertSee('signup@example.com')
            ->assertSee('Jane Parent')
            ->assertSee('>2<', false);
    }

    public function test_login_writes_learner_login_event(): void
    {
        $org = $this->summerOrg();
        $parent = $this->registerParent($org, 'login-parent@example.com', 1);

        $this->post(route('org.student.logout', $org));

        $this->post(route('org.student.login.submit', $org), [
            'email' => 'login-parent@example.com',
            'password' => 'password123',
        ])->assertRedirect();

        $this->assertDatabaseHas('learner_login_events', [
            'organization_id' => $org->id,
            'user_id' => $parent->id,
        ]);

        $this->assertGreaterThanOrEqual(2, LearnerLoginEvent::where('user_id', $parent->id)->count());
    }

    public function test_visiting_lesson_tracks_visit_and_lessons_logout_ends_visit(): void
    {
        $org = $this->summerOrg();
        $parent = $this->registerParent($org, 'visit@example.com', 1);
        $child = ParentStudent::where('parent_id', $parent->id)->firstOrFail();

        $course = Course::create([
            'title' => 'Summer Course',
            'slug' => 'summer-course-test',
            'is_active' => true,
        ]);
        $org->courses()->attach($course->id, ['is_org_wide' => true]);

        $lesson = Lesson::create([
            'title' => 'Day 1 Introductions',
            'slug' => 'day-1-introductions-analytics',
            'is_active' => true,
            'course_id' => $course->id,
        ]);

        $this->actingAs($parent, 'admin')
            ->withSession(['selected_student_id' => $child->id])
            ->get(route('org.student.lesson', ['organization' => $org, 'slug' => $lesson->slug]))
            ->assertOk();

        $visit = LearnerVisit::where('organization_id', $org->id)
            ->where('user_id', $parent->id)
            ->whereNull('ended_at')
            ->first();

        $this->assertNotNull($visit);
        $this->assertDatabaseHas('learner_visit_lessons', [
            'learner_visit_id' => $visit->id,
            'lesson_id' => $lesson->id,
        ]);

        $this->post(route('org.student.logout', $org))->assertRedirect();

        $visit->refresh();
        $this->assertNotNull($visit->ended_at);
        $this->assertSame(LearnerVisit::END_REASON_LOGOUT, $visit->end_reason);
    }

    public function test_idle_visit_is_closed_and_new_visit_starts(): void
    {
        $org = $this->summerOrg();
        $parent = $this->registerParent($org, 'idle@example.com', 1);

        $tracker = app(LearnerVisitTracker::class);
        $visit = $tracker->startOrResumeVisit($parent, $org);
        $visit->update(['last_seen_at' => now()->subMinutes(LearnerVisit::IDLE_MINUTES + 5)]);

        $newVisit = $tracker->startOrResumeVisit($parent, $org);

        $this->assertNotSame($visit->id, $newVisit->id);
        $visit->refresh();
        $this->assertSame(LearnerVisit::END_REASON_IDLE, $visit->end_reason);
        $this->assertNotNull($visit->ended_at);
    }

    public function test_org_member_can_view_summer_analytics_non_member_forbidden(): void
    {
        $org = $this->summerOrg();
        $member = User::factory()->teacher()->create();
        $org->users()->attach($member->id, ['role' => 'org_admin']);

        $this->actingAs($member, 'admin')
            ->get(route('org.admin.summer-analytics', $org))
            ->assertOk();

        $outsider = User::factory()->teacher()->create();
        $this->actingAs($outsider, 'admin')
            ->get(route('org.admin.summer-analytics', $org))
            ->assertForbidden();
    }

    public function test_non_summer_org_returns_404(): void
    {
        $org = Organization::create([
            'name' => 'Other Org',
            'slug' => 'other-org',
            'access_mode' => 'restricted',
            'is_active' => true,
        ]);
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin, 'admin')
            ->get(route('org.admin.summer-analytics', $org))
            ->assertNotFound();
    }

    public function test_csv_exports_work(): void
    {
        $org = $this->summerOrg();
        $this->registerParent($org, 'csv@example.com', 1);
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin, 'admin')
            ->get(route('org.admin.summer-analytics.export-signups', $org))
            ->assertOk()
            ->assertHeader('content-disposition');

        $this->actingAs($admin, 'admin')
            ->get(route('org.admin.summer-analytics.export-logins', $org))
            ->assertOk();

        $this->actingAs($admin, 'admin')
            ->get(route('org.admin.summer-analytics.export-visits', $org))
            ->assertOk();
    }
}
