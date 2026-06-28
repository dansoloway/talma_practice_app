<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrganizationAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_admin_can_create_organization(): void
    {
        $admin = User::where('role', 'admin')->firstOrFail();
        $this->actingAs($admin, 'admin');

        $response = $this->post(route('admin.organizations.store'), [
            'name' => 'New School',
            'slug' => 'new-school',
            'access_mode' => 'open',
            'registration_type' => 'student',
            'is_active' => true,
        ]);
        $response->assertRedirect(route('admin.organizations.index'));
        $this->assertDatabaseHas('organizations', ['slug' => 'new-school', 'name' => 'New School']);
    }

    public function test_admin_can_edit_organization(): void
    {
        $admin = User::where('role', 'admin')->firstOrFail();
        $this->actingAs($admin, 'admin');
        $org = Organization::where('slug', 'default')->firstOrFail();

        $response = $this->put(route('admin.organizations.update', $org), [
            'name' => 'TALMA Community (Updated)',
            'slug' => 'default',
            'access_mode' => 'open',
            'registration_type' => 'student',
            'is_active' => true,
        ]);
        $response->assertRedirect(route('admin.organizations.index'));
        $org->refresh();
        $this->assertEquals('TALMA Community (Updated)', $org->name);
    }

    public function test_teacher_cannot_access_organizations_index(): void
    {
        $teacher = User::where('role', 'teacher')->first();
        if (!$teacher) {
            $this->markTestSkipped('No teacher user');
        }
        $this->actingAs($teacher, 'admin');

        $response = $this->get(route('admin.organizations.index'));
        $response->assertStatus(403);
    }
}
