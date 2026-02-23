<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_get_admin_login_returns_200(): void
    {
        $response = $this->get('/admin/login');

        $response->assertStatus(200);
    }

    public function test_successful_login_with_no_last_org_redirects_to_org_select(): void
    {
        User::factory()->admin()->create([
            'email' => 'admin@test.com',
        ]);

        $response = $this->post('/admin/login', [
            'email' => 'admin@test.com',
            'password' => 'password',
        ]);

        $response->assertRedirect(route('admin.org.select'));

        $this->get(route('admin.org.select'));
        $this->assertAuthenticated('admin');
    }

    public function test_successful_login_with_valid_last_org_redirects_to_org_admin(): void
    {
        $user = User::factory()->admin()->create([
            'email' => 'admin@test.com',
        ]);
        $org = \App\Models\Organization::create([
            'name' => 'Test Org',
            'slug' => 'test-org',
            'is_active' => true,
        ]);

        $response = $this->withSession(['admin_last_org_slug' => $org->slug])
            ->post('/admin/login', [
                'email' => 'admin@test.com',
                'password' => 'password',
            ]);

        $response->assertRedirect(route('org.admin.analytics', ['organization' => $org->slug]));
    }

    public function test_inactive_user_cannot_login_and_remains_guest(): void
    {
        User::factory()->admin()->inactive()->create([
            'email' => 'inactive@test.com',
        ]);

        $response = $this->post('/admin/login', [
            'email' => 'inactive@test.com',
            'password' => 'password',
        ]);

        $response->assertRedirect('/admin/login');
        $response->assertSessionHasErrors('email');

        $this->assertFalse(auth('admin')->check());
    }

    public function test_post_admin_logout_logs_out_and_redirects_to_admin_login(): void
    {
        $user = User::factory()->admin()->create();

        $this->post('/admin/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertTrue(auth('admin')->check());

        $response = $this->post(route('admin.logout'));

        $response->assertRedirect();
        $redirectLocation = $response->headers->get('Location');
        $this->assertStringContainsString('/admin/login', $redirectLocation);

        $this->assertFalse(auth('admin')->check());
    }

    public function test_post_admin_logout_with_accept_json_returns_json_including_redirect(): void
    {
        $user = User::factory()->admin()->create();

        $this->post('/admin/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response = $this->postJson(route('admin.logout'));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'redirect' => route('admin.login.show'),
        ]);
    }
}
