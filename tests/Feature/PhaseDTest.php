<?php

namespace Tests\Feature;

use App\Models\Classroom;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Organization;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PhaseDTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_default_org_courses_all_have_is_org_wide_true(): void
    {
        $defaultOrg = Organization::where('slug', 'default')->firstOrFail();
        $pivots = DB::table('organization_course')
            ->where('organization_id', $defaultOrg->id)
            ->get();
        $this->assertGreaterThan(0, $pivots->count(), 'Default org must have courses');
        foreach ($pivots as $pivot) {
            $this->assertTrue((bool) $pivot->is_org_wide, "Default org course {$pivot->course_id} must be org-wide");
        }
    }

    public function test_guest_can_access_courses_slug_legacy_route(): void
    {
        $course = Course::whereNull('archived_at')->where('is_active', true)->first();
        if (!$course) {
            $this->markTestSkipped('No active course to test');
        }
        $response = $this->get(route('student.course', $course->slug));
        $response->assertOk();
    }

    public function test_guest_can_access_lessons_slug_legacy_route(): void
    {
        $lesson = Lesson::where('is_active', true)->first();
        if (!$lesson) {
            $this->markTestSkipped('No active lesson to test');
        }
        $response = $this->get(route('lessons.show', $lesson->slug));
        $response->assertOk();
    }

    public function test_guest_can_access_open_org_student_index(): void
    {
        $defaultOrg = Organization::where('slug', 'default')->firstOrFail();
        $response = $this->get(route('org.student.index', $defaultOrg->slug));
        $response->assertOk();
    }

    public function test_guest_can_access_open_org_student_course(): void
    {
        $defaultOrg = Organization::where('slug', 'default')->firstOrFail();
        $course = $defaultOrg->courses()->first();
        if (!$course) {
            $this->markTestSkipped('No course in Default org');
        }
        $response = $this->get(route('org.student.course', [$defaultOrg->slug, $course->slug]));
        $response->assertOk();
    }

    public function test_guest_redirected_to_login_on_restricted_org(): void
    {
        $org = Organization::create([
            'name' => 'Restricted Org',
            'slug' => 'restricted-org',
            'access_mode' => 'restricted',
            'is_active' => true,
        ]);
        $response = $this->get(route('org.student.index', $org->slug));
        $response->assertRedirect(route('org.student.login', $org));
    }

    public function test_logged_in_member_can_access_restricted_org_org_wide_course(): void
    {
        $org = Organization::create([
            'name' => 'Restricted Org',
            'slug' => 'restricted-org',
            'access_mode' => 'restricted',
            'is_active' => true,
        ]);
        $user = User::factory()->teacher()->create();
        $org->users()->attach($user->id, ['role' => 'student']);
        $course = Course::whereNull('archived_at')->where('is_active', true)->first();
        $org->courses()->attach($course->id, ['is_org_wide' => true]);

        $response = $this->actingAs($user, 'admin')
            ->get(route('org.student.index', $org->slug));
        $response->assertOk();

        $response = $this->actingAs($user, 'admin')
            ->get(route('org.student.course', [$org->slug, $course->slug]));
        $response->assertOk();
    }

    public function test_student_in_class_can_access_class_only_course(): void
    {
        $org = Organization::create([
            'name' => 'Restricted Org',
            'slug' => 'restricted-org',
            'access_mode' => 'restricted',
            'is_active' => true,
        ]);
        $user = User::factory()->create(['role' => 'teacher']);
        $org->users()->attach($user->id, ['role' => 'student']);
        $course = Course::whereNull('archived_at')->where('is_active', true)->first();
        $org->courses()->attach($course->id, ['is_org_wide' => false]); // class-only

        $classroom = $org->classes()->create(['name' => 'Class A', 'slug' => 'class-a']);
        $classroom->students()->attach($user->id);
        $classroom->courses()->attach($course->id);

        $response = $this->actingAs($user, 'admin')
            ->get(route('org.student.course', [$org->slug, $course->slug]));
        $response->assertOk();
    }

    public function test_student_not_in_class_cannot_access_class_only_course(): void
    {
        $org = Organization::create([
            'name' => 'Restricted Org',
            'slug' => 'restricted-org',
            'access_mode' => 'restricted',
            'is_active' => true,
        ]);
        $user = User::factory()->create(['role' => 'teacher']);
        $org->users()->attach($user->id, ['role' => 'student']);
        $course = Course::whereNull('archived_at')->where('is_active', true)->first();
        $org->courses()->attach($course->id, ['is_org_wide' => false]); // class-only

        $classroom = $org->classes()->create(['name' => 'Class A', 'slug' => 'class-a']);
        $classroom->courses()->attach($course->id);
        // User NOT in class

        $response = $this->actingAs($user, 'admin')
            ->get(route('org.student.course', [$org->slug, $course->slug]));
        $response->assertForbidden();
    }

    public function test_guest_can_access_org_student_lesson(): void
    {
        $defaultOrg = Organization::where('slug', 'default')->firstOrFail();
        $lesson = Lesson::where('is_active', true)->first();
        if (!$lesson) {
            $this->markTestSkipped('No active lesson to test');
        }
        $response = $this->get(route('org.student.lesson', [$defaultOrg->slug, $lesson->slug]));
        $response->assertOk();
    }

    public function test_trailing_slash_org_index_does_not_404(): void
    {
        $defaultOrg = Organization::where('slug', 'default')->firstOrFail();
        $response = $this->get('/o/' . $defaultOrg->slug . '/');
        $this->assertTrue($response->isRedirect() || $response->isSuccessful(), 'Trailing slash should redirect or load');
    }

    public function test_cross_org_same_course_different_access(): void
    {
        $course = Course::whereNull('archived_at')->where('is_active', true)->first();
        if (!$course) {
            $this->markTestSkipped('No active course');
        }

        $orgA = Organization::create([
            'name' => 'Org A',
            'slug' => 'org-a',
            'access_mode' => 'open',
            'is_active' => true,
        ]);
        $orgA->courses()->attach($course->id, ['is_org_wide' => true]);

        $orgB = Organization::create([
            'name' => 'Org B',
            'slug' => 'org-b',
            'access_mode' => 'restricted',
            'is_active' => true,
        ]);
        $orgB->courses()->attach($course->id, ['is_org_wide' => false]); // class-only in B

        $user = User::factory()->create(['role' => 'teacher']);
        $orgB->users()->attach($user->id, ['role' => 'student']);
        $classroom = $orgB->classes()->create(['name' => 'Class B', 'slug' => 'class-b']);
        $classroom->students()->attach($user->id);
        $classroom->courses()->attach($course->id);

        $guestResponseA = $this->get(route('org.student.course', [$orgA->slug, $course->slug]));
        $guestResponseA->assertOk();

        $guestResponseB = $this->get(route('org.student.course', [$orgB->slug, $course->slug]));
        $guestResponseB->assertRedirect(route('org.student.login', $orgB));

        $memberResponseB = $this->actingAs($user, 'admin')
            ->get(route('org.student.course', [$orgB->slug, $course->slug]));
        $memberResponseB->assertOk();
    }
}
