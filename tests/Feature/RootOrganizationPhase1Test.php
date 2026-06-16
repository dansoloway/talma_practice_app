<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Organization;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RootOrganizationPhase1Test extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_root_org_exists_after_initialization(): void
    {
        $root = Organization::root();
        $this->assertNotNull($root);
        $this->assertTrue($root->is_root);
        $this->assertEquals('root', $root->slug);
    }

    public function test_only_one_root_exists(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Only one Root organization may exist');

        Organization::create([
            'name' => 'Fake Root',
            'slug' => 'fake-root',
            'is_root' => true,
        ]);
    }

    public function test_non_global_admin_cannot_access_attach_from_root(): void
    {
        $teacher = User::where('role', 'teacher')->first();
        if (!$teacher) {
            $this->markTestSkipped('No teacher user');
        }
        $this->actingAs($teacher, 'admin');
        $defaultOrg = Organization::where('slug', 'default')->firstOrFail();

        $response = $this->get(route('org.admin.courses.add-from-root', ['organization' => $defaultOrg->slug]));
        $response->assertStatus(403);
    }

    public function test_non_global_admin_cannot_post_attach_from_root(): void
    {
        $teacher = User::where('role', 'teacher')->first();
        if (!$teacher) {
            $this->markTestSkipped('No teacher user');
        }
        $this->actingAs($teacher, 'admin');
        $defaultOrg = Organization::where('slug', 'default')->firstOrFail();
        $course = Course::firstOrFail();

        $response = $this->post(route('org.admin.courses.attach-from-root', ['organization' => $defaultOrg->slug]), [
            'course_id' => $course->id,
            'is_org_wide' => 1,
        ]);
        $response->assertStatus(403);
    }

    public function test_global_admin_can_attach_root_course_to_tenant_org(): void
    {
        $admin = User::where('role', 'admin')->firstOrFail();
        $this->actingAs($admin, 'admin');

        $root = Organization::root();
        $defaultOrg = Organization::where('slug', 'default')->firstOrFail();
        $course = Course::firstOrFail();

        $root->courses()->syncWithoutDetaching([$course->id => ['is_org_wide' => true]]);

        $weSpeakOrg = Organization::create([
            'name' => 'We Speak',
            'slug' => 'we-speak',
            'is_active' => true,
            'access_mode' => 'open',
            'is_root' => false,
        ]);

        $response = $this->post(route('org.admin.courses.attach-from-root', ['organization' => $weSpeakOrg->slug]), [
            'course_id' => $course->id,
            'is_org_wide' => 1,
        ]);
        $response->assertRedirect(route('org.admin.courses.index', ['organization' => $weSpeakOrg->slug]));

        $this->assertTrue($weSpeakOrg->courses()->whereKey($course->id)->exists());
    }

    public function test_attach_creates_pivot_only_root_course_persists(): void
    {
        $admin = User::where('role', 'admin')->firstOrFail();
        $this->actingAs($admin, 'admin');

        $root = Organization::root();
        $defaultOrg = Organization::where('slug', 'default')->firstOrFail();
        $course = Course::firstOrFail();
        $root->courses()->syncWithoutDetaching([$course->id => ['is_org_wide' => true]]);

        $weSpeakOrg = Organization::create([
            'name' => 'We Speak',
            'slug' => 'we-speak',
            'is_active' => true,
            'access_mode' => 'open',
            'is_root' => false,
        ]);

        $this->post(route('org.admin.courses.attach-from-root', ['organization' => $weSpeakOrg->slug]), [
            'course_id' => $course->id,
            'is_org_wide' => 1,
        ]);

        $this->assertTrue($root->courses()->whereKey($course->id)->exists());
        $this->assertTrue($weSpeakOrg->courses()->whereKey($course->id)->exists());
        $this->assertEquals(1, Course::whereKey($course->id)->count());
    }

    public function test_detach_removes_pivot_only_root_course_persists(): void
    {
        $admin = User::where('role', 'admin')->firstOrFail();
        $this->actingAs($admin, 'admin');

        $root = Organization::root();
        $defaultOrg = Organization::where('slug', 'default')->firstOrFail();
        $course = Course::firstOrFail();
        $root->courses()->syncWithoutDetaching([$course->id => ['is_org_wide' => true]]);
        // defaultOrg already has course from OrganizationSeeder

        $response = $this->post(route('org.admin.courses.detach-from-org', [
            'organization' => $defaultOrg->slug,
            'course' => $course,
        ]));
        $response->assertRedirect(route('org.admin.courses.index', ['organization' => $defaultOrg->slug]));

        $this->assertFalse($defaultOrg->fresh()->courses()->whereKey($course->id)->exists());
        $this->assertTrue($root->courses()->whereKey($course->id)->exists());
        $this->assertDatabaseHas('courses', ['id' => $course->id]);
    }

    public function test_tenant_org_admin_cannot_update_root_course(): void
    {
        $teacher = User::where('role', 'teacher')->first();
        if (!$teacher) {
            $this->markTestSkipped('No teacher user');
        }
        $root = Organization::root();
        $defaultOrg = Organization::where('slug', 'default')->firstOrFail();
        $defaultOrg->users()->syncWithoutDetaching([$teacher->id => ['role' => 'org_admin']]);

        $course = Course::firstOrFail();
        $root->courses()->syncWithoutDetaching([$course->id => ['is_org_wide' => true]]);
        $defaultOrg->courses()->attach($course->id, ['is_org_wide' => true]);

        $this->actingAs($teacher, 'admin');

        $response = $this->put(route('org.admin.courses.update', [
            'organization' => $defaultOrg->slug,
            'course' => $course->slug,
        ]), [
            'title' => $course->title,
            'slug' => $course->slug,
            'is_active' => 1,
        ]);
        $response->assertStatus(403);
    }

    public function test_global_admin_can_update_root_course_in_root_context(): void
    {
        $admin = User::where('role', 'admin')->firstOrFail();
        $this->actingAs($admin, 'admin');

        $root = Organization::root();
        $course = Course::firstOrFail();
        $root->courses()->syncWithoutDetaching([$course->id => ['is_org_wide' => true]]);

        $response = $this->put(route('org.admin.courses.update', [
            'organization' => $root,
            'course' => $course,
        ]), [
            'title' => 'Updated Title',
            'slug' => $course->slug,
            'is_active' => 1,
        ]);
        $response->assertRedirect();

        $course->refresh();
        $this->assertEquals('Updated Title', $course->title);
    }
}
