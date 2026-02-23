<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Organization;
use App\Models\User;
use Database\Seeders\CourseSeeder;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\OrganizationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrganizationSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_organization_seeder_creates_default_org_and_attaches_courses_and_users(): void
    {
        $admin = User::factory()->admin()->create();
        $teacher = User::factory()->teacher()->create();
        User::factory()->create(['role' => 'teacher', 'email' => 'teacher2@test.com']); // second teacher

        $course1 = Course::create([
            'title' => 'Test Course 1',
            'slug' => 'test-course-1',
            'is_active' => true,
        ]);
        $course2 = Course::create([
            'title' => 'Test Course 2',
            'slug' => 'test-course-2',
            'is_active' => true,
        ]);

        $this->seed(OrganizationSeeder::class);

        $defaultOrg = Organization::where('slug', 'default')->first();
        $this->assertNotNull($defaultOrg);
        $this->assertEquals('Default', $defaultOrg->name);
        $this->assertEquals('open', $defaultOrg->access_mode);
        $this->assertTrue($defaultOrg->is_active);

        $this->assertCount(2, $defaultOrg->courses);
        $this->assertEqualsCanonicalizing(
            [$course1->id, $course2->id],
            $defaultOrg->courses->pluck('id')->toArray()
        );

        $adminTeacherCount = User::whereIn('role', ['admin', 'teacher'])->count();
        $this->assertCount($adminTeacherCount, $defaultOrg->users);
        foreach ($defaultOrg->users as $user) {
            $this->assertContains($user->role, ['admin', 'teacher']);
            $this->assertEquals('org_admin', $user->pivot->role);
        }
    }

    public function test_organization_seeder_is_idempotent(): void
    {
        User::factory()->admin()->create();
        Course::create([
            'title' => 'Test Course',
            'slug' => 'test-course',
            'is_active' => true,
        ]);

        $this->seed(OrganizationSeeder::class);
        $this->seed(OrganizationSeeder::class);

        $defaultOrg = Organization::where('slug', 'default')->first();
        $this->assertCount(1, $defaultOrg->courses);
        $this->assertCount(1, $defaultOrg->users);
    }

    public function test_fresh_seed_chain_produces_courses_and_default_org_has_them(): void
    {
        $this->seed(DatabaseSeeder::class);

        $courseCount = Course::count();
        $this->assertGreaterThan(0, $courseCount, 'Fresh seed must produce at least 1 course');

        $defaultOrg = Organization::where('slug', 'default')->first();
        $this->assertNotNull($defaultOrg);
        $this->assertCount($courseCount, $defaultOrg->courses, 'Default org must have all courses attached');
    }

    public function test_fresh_seed_chain_is_idempotent(): void
    {
        $this->seed(DatabaseSeeder::class);
        $courseCountAfterFirst = Course::count();
        $orgCourseCountAfterFirst = Organization::where('slug', 'default')->first()->courses()->count();

        // Run CourseSeeder and OrganizationSeeder again (they are idempotent)
        $this->seed(CourseSeeder::class);
        $this->seed(OrganizationSeeder::class);

        $this->assertEquals($courseCountAfterFirst, Course::count(), 'Second run must not duplicate courses');
        $this->assertEquals(
            $orgCourseCountAfterFirst,
            Organization::where('slug', 'default')->first()->courses()->count(),
            'Second run must not duplicate organization_course pivot rows'
        );
    }
}
