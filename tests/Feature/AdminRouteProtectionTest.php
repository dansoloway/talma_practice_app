<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminRouteProtectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_get_admin_analytics_redirects_to_admin_login(): void
    {
        $response = $this->get('/admin/analytics');

        $response->assertRedirect();
        $redirectLocation = $response->headers->get('Location');
        $this->assertStringContainsString('/admin/login', $redirectLocation);
    }

    public function test_teacher_cannot_access_admin_users_returns_403(): void
    {
        $teacher = User::factory()->teacher()->create();

        $response = $this->actingAs($teacher, 'admin')->get(route('admin.users.index'));

        $response->assertStatus(403);
    }

    public function test_admin_who_becomes_inactive_after_login_is_logged_out_on_next_admin_request(): void
    {
        $admin = User::factory()->admin()->inactive()->create();

        // Use actingAs to simulate session with inactive user (e.g. was deactivated after login)
        $this->actingAs($admin, 'admin');

        $response = $this->get(route('admin.grammar-concepts.index'));

        $response->assertRedirect();
        $redirectLocation = $response->headers->get('Location');
        $this->assertStringContainsString('/admin/login', $redirectLocation);

        $this->assertGuest('admin');
    }
}
