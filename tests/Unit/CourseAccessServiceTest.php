<?php

namespace Tests\Unit;

use App\Models\Classroom;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Organization;
use App\Models\User;
use App\Services\CourseAccess;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourseAccessServiceTest extends TestCase
{
    use RefreshDatabase;

    protected CourseAccess $courseAccess;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->courseAccess = app(CourseAccess::class);
    }

    public function test_org_wide_course_accessible_by_guest(): void
    {
        $org = Organization::where('slug', 'default')->firstOrFail();
        $course = $org->courses()->first();
        $this->assertTrue($this->courseAccess->canAccessCourse(null, $course, $org));
    }

    public function test_org_wide_course_accessible_by_member(): void
    {
        $org = Organization::where('slug', 'default')->firstOrFail();
        $course = $org->courses()->first();
        $user = User::factory()->create();
        $org->users()->attach($user->id, ['role' => 'student']);
        $this->assertTrue($this->courseAccess->canAccessCourse($user, $course, $org));
    }

    public function test_course_not_in_org_denied(): void
    {
        $org = Organization::create([
            'name' => 'New Org',
            'slug' => 'new-org',
            'is_active' => true,
        ]);
        $course = Course::whereNull('archived_at')->where('is_active', true)->first();
        $this->assertFalse($this->courseAccess->canAccessCourse(null, $course, $org));
    }

    public function test_class_only_course_denied_for_guest(): void
    {
        $org = Organization::create([
            'name' => 'Org',
            'slug' => 'org',
            'access_mode' => 'restricted',
            'is_active' => true,
        ]);
        $course = Course::whereNull('archived_at')->where('is_active', true)->first();
        $org->courses()->attach($course->id, ['is_org_wide' => false]);
        $classroom = $org->classes()->create(['name' => 'Class 1', 'slug' => 'class-1']);
        $classroom->courses()->attach($course->id);

        $this->assertFalse($this->courseAccess->canAccessCourse(null, $course, $org));
    }

    public function test_class_only_course_accessible_when_user_in_class(): void
    {
        $org = Organization::create([
            'name' => 'Org',
            'slug' => 'org',
            'is_active' => true,
        ]);
        $user = User::factory()->create();
        $org->users()->attach($user->id, ['role' => 'student']);
        $course = Course::whereNull('archived_at')->where('is_active', true)->first();
        $org->courses()->attach($course->id, ['is_org_wide' => false]);
        $classroom = $org->classes()->create(['name' => 'Class 1', 'slug' => 'class-1']);
        $classroom->students()->attach($user->id);
        $classroom->courses()->attach($course->id);

        $this->assertTrue($this->courseAccess->canAccessCourse($user, $course, $org));
    }

    public function test_class_only_course_denied_when_user_not_in_class(): void
    {
        $org = Organization::create([
            'name' => 'Org',
            'slug' => 'org',
            'is_active' => true,
        ]);
        $user = User::factory()->create();
        $org->users()->attach($user->id, ['role' => 'student']);
        $course = Course::whereNull('archived_at')->where('is_active', true)->first();
        $org->courses()->attach($course->id, ['is_org_wide' => false]);
        $classroom = $org->classes()->create(['name' => 'Class 1', 'slug' => 'class-1']);
        $classroom->courses()->attach($course->id);
        // User NOT in class

        $this->assertFalse($this->courseAccess->canAccessCourse($user, $course, $org));
    }

    public function test_can_access_lesson_delegates_to_course(): void
    {
        $org = Organization::where('slug', 'default')->firstOrFail();
        $lesson = Lesson::where('is_active', true)->whereNotNull('course_id')->first();
        if (!$lesson) {
            $this->markTestSkipped('No lesson with course');
        }
        $this->assertTrue($this->courseAccess->canAccessLesson(null, $lesson, $org));
    }

    public function test_lesson_without_course_denied(): void
    {
        $lesson = Lesson::create([
            'title' => 'Orphan Lesson',
            'slug' => 'orphan-lesson',
            'course_id' => null,
            'is_active' => true,
        ]);
        $org = Organization::where('slug', 'default')->firstOrFail();
        $this->assertFalse($this->courseAccess->canAccessLesson(null, $lesson, $org));
    }

    public function test_accessible_courses_returns_org_wide_for_guest(): void
    {
        $org = Organization::where('slug', 'default')->firstOrFail();
        $courses = $this->courseAccess->accessibleCourses(null, $org)->get();
        $this->assertGreaterThan(0, $courses->count());
    }
}
