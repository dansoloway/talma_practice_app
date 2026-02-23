<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrgMembershipEnforcementTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_without_org_membership_gets_403_on_org_admin_analytics(): void
    {
        $teacher = User::factory()->teacher()->create();
        $org = Organization::create(['name' => 'Other Org', 'slug' => 'other-org', 'is_active' => true]);
        // Teacher is NOT a member of this org

        $response = $this->actingAs($teacher, 'admin')
            ->get(route('org.admin.courses.index', ['organization' => $org->slug]));

        $response->assertStatus(403);
    }

    public function test_teacher_with_org_membership_can_access_org_admin_analytics(): void
    {
        $teacher = User::factory()->teacher()->create();
        $org = Organization::create(['name' => 'My Org', 'slug' => 'my-org', 'is_active' => true]);
        $org->users()->attach($teacher->id, ['role' => 'teacher']);

        $response = $this->actingAs($teacher, 'admin')
            ->get(route('org.admin.courses.index', ['organization' => $org->slug]));

        $response->assertStatus(200);
    }

    public function test_org_admin_with_org_membership_can_access_org_admin_analytics(): void
    {
        $teacher = User::factory()->teacher()->create();
        $org = Organization::create(['name' => 'My Org', 'slug' => 'my-org', 'is_active' => true]);
        $org->users()->attach($teacher->id, ['role' => 'org_admin']);

        $response = $this->actingAs($teacher, 'admin')
            ->get(route('org.admin.courses.index', ['organization' => $org->slug]));

        $response->assertStatus(200);
    }

    public function test_global_admin_can_access_any_org_without_membership(): void
    {
        $admin = User::factory()->admin()->create();
        $org = Organization::create(['name' => 'Some Org', 'slug' => 'some-org', 'is_active' => true]);
        // Admin is NOT a member of this org

        $response = $this->actingAs($admin, 'admin')
            ->get(route('org.admin.courses.index', ['organization' => $org->slug]));

        $response->assertStatus(200);
    }
}
