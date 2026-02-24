<?php

namespace Tests\Feature;

use App\Models\Classroom;
use App\Models\Course;
use App\Models\Organization;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClassroomAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_org_admin_can_access_classrooms_index(): void
    {
        $org = Organization::where('slug', 'default')->firstOrFail();
        $user = User::factory()->admin()->create();
        $org->users()->attach($user->id, ['role' => 'org_admin']);

        $response = $this->actingAs($user, 'admin')
            ->get(route('org.admin.classrooms.index', ['organization' => $org->slug]));
        $response->assertOk();
    }

    public function test_org_admin_can_create_classroom(): void
    {
        $org = Organization::where('slug', 'default')->firstOrFail();
        $user = User::factory()->admin()->create();
        $org->users()->attach($user->id, ['role' => 'org_admin']);

        $response = $this->actingAs($user, 'admin')
            ->post(route('org.admin.classrooms.store', ['organization' => $org->slug]), [
                'name' => 'Test Class',
            ]);
        $response->assertRedirect();
        $this->assertDatabaseHas('classrooms', [
            'organization_id' => $org->id,
            'name' => 'Test Class',
        ]);
    }

    public function test_org_admin_can_assign_courses_to_classroom(): void
    {
        $org = Organization::where('slug', 'default')->firstOrFail();
        $user = User::factory()->admin()->create();
        $org->users()->attach($user->id, ['role' => 'org_admin']);
        $course = $org->courses()->first();
        $classroom = $org->classes()->create(['name' => 'Test Class', 'slug' => 'test-class']);

        $response = $this->actingAs($user, 'admin')
            ->post(route('org.admin.classrooms.sync-courses', [
                'organization' => $org->slug,
                'classroom' => $classroom->slug,
            ]), [
                'course_ids' => [$course->id],
            ]);
        $response->assertRedirect();
        $this->assertTrue($classroom->fresh()->courses->contains($course));
    }

    public function test_org_admin_can_assign_students_to_classroom(): void
    {
        $org = Organization::where('slug', 'default')->firstOrFail();
        $user = User::factory()->admin()->create();
        $student = User::factory()->create();
        $org->users()->attach($user->id, ['role' => 'org_admin']);
        $org->users()->attach($student->id, ['role' => 'student']);
        $classroom = $org->classes()->create(['name' => 'Test Class', 'slug' => 'test-class']);

        $response = $this->actingAs($user, 'admin')
            ->post(route('org.admin.classrooms.sync-students', [
                'organization' => $org->slug,
                'classroom' => $classroom->slug,
            ]), [
                'user_ids' => [$student->id],
            ]);
        $response->assertRedirect();
        $this->assertTrue($classroom->fresh()->students->contains($student));
    }
}
