<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrgSelectTest extends TestCase
{
    use RefreshDatabase;

    public function test_logged_in_admin_hitting_admin_redirects_to_org_select_when_no_last_org(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin, 'admin')->get(route('admin.dashboard'));

        $response->assertRedirect();
        $redirectLocation = $response->headers->get('Location');
        $this->assertStringContainsString('/admin/org/select', $redirectLocation);
    }

    public function test_logged_in_admin_hitting_admin_redirects_to_last_org_when_last_org_set(): void
    {
        $admin = User::factory()->admin()->create();
        $org = Organization::create([
            'name' => 'Test Org',
            'slug' => 'test-org',
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin, 'admin')
            ->withSession(['admin_last_org_slug' => $org->slug])
            ->get(route('admin.dashboard'));

        $response->assertRedirect(route('org.admin.analytics', ['organization' => $org->slug]));
    }

    public function test_logged_in_admin_with_invalid_last_org_redirects_to_org_select_and_clears_session(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin, 'admin')
            ->withSession(['admin_last_org_slug' => 'nonexistent-org'])
            ->get(route('admin.dashboard'));

        $response->assertRedirect(route('admin.org.select'));
        $response->assertSessionMissing('admin_last_org_slug');
    }

    public function test_teacher_with_last_org_they_no_longer_have_access_to_redirects_to_org_select(): void
    {
        $teacher = User::factory()->teacher()->create();
        $org = Organization::create([
            'name' => 'Other Org',
            'slug' => 'other-org',
            'is_active' => true,
        ]);
        // Teacher is NOT a member of other-org

        $response = $this->actingAs($teacher, 'admin')
            ->withSession(['admin_last_org_slug' => $org->slug])
            ->get(route('admin.dashboard'));

        $response->assertRedirect(route('admin.org.select'));
        $response->assertSessionMissing('admin_last_org_slug');
    }

    public function test_org_select_page_lists_all_orgs_for_global_admin(): void
    {
        $admin = User::factory()->admin()->create();
        $orgA = Organization::create(['name' => 'Org A', 'slug' => 'org-a', 'is_active' => true]);
        $orgB = Organization::create(['name' => 'Org B', 'slug' => 'org-b', 'is_active' => true]);

        $response = $this->actingAs($admin, 'admin')->get(route('admin.org.select'));

        $response->assertStatus(200);
        $response->assertSee('Org A');
        $response->assertSee('Org B');
    }

    public function test_org_select_page_lists_only_membership_orgs_for_teacher(): void
    {
        $teacher = User::factory()->teacher()->create();
        $orgA = Organization::create(['name' => 'Org A', 'slug' => 'org-a', 'is_active' => true]);
        $orgB = Organization::create(['name' => 'Org B', 'slug' => 'org-b', 'is_active' => true]);

        // Teacher is member of org A only
        $orgA->users()->attach($teacher->id, ['role' => 'teacher']);

        $response = $this->actingAs($teacher, 'admin')->get(route('admin.org.select'));

        $response->assertStatus(200);
        $response->assertSee('Org A');
        $response->assertDontSee('Org B');
    }

    public function test_post_org_select_stores_last_org_and_redirects_to_org_admin(): void
    {
        $admin = User::factory()->admin()->create();
        $org = Organization::create(['name' => 'Test Org', 'slug' => 'test-org', 'is_active' => true]);

        $response = $this->actingAs($admin, 'admin')->post(route('admin.org.select.store'), [
            'organization' => $org->slug,
            '_token' => csrf_token(),
        ]);

        $response->assertRedirect(route('org.admin.analytics', ['organization' => $org->slug]));
        $this->assertEquals($org->slug, session('admin_last_org_slug'));
    }
}
