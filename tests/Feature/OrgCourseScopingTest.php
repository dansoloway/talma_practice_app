<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrgCourseScopingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->admin()->create();
    }

    private User $admin;

    public function test_course_attached_to_both_orgs_appears_in_both_indexes(): void
    {
        $orgA = Organization::create(['name' => 'Org A', 'slug' => 'org-a', 'is_active' => true]);
        $orgB = Organization::create(['name' => 'Org B', 'slug' => 'org-b', 'is_active' => true]);

        $course = Course::create([
            'title' => 'Shared Course',
            'slug' => 'shared-course',
            'is_active' => true,
        ]);
        $orgA->courses()->attach($course->id);
        $orgB->courses()->attach($course->id);

        $responseA = $this->actingAs($this->admin, 'admin')
            ->get(route('org.admin.courses.index', ['organization' => $orgA->slug]));
        $responseB = $this->actingAs($this->admin, 'admin')
            ->get(route('org.admin.courses.index', ['organization' => $orgB->slug]));

        $responseA->assertStatus(200);
        $responseB->assertStatus(200);
        $responseA->assertSee('Shared Course');
        $responseB->assertSee('Shared Course');
    }

    public function test_course_attached_only_to_org_a_appears_in_a_index_not_in_b(): void
    {
        $orgA = Organization::create(['name' => 'Org A', 'slug' => 'org-a', 'is_active' => true]);
        $orgB = Organization::create(['name' => 'Org B', 'slug' => 'org-b', 'is_active' => true]);

        $courseAOnly = Course::create([
            'title' => 'Org A Only Course',
            'slug' => 'org-a-only-course',
            'is_active' => true,
        ]);
        $orgA->courses()->attach($courseAOnly->id);

        $responseA = $this->actingAs($this->admin, 'admin')
            ->get(route('org.admin.courses.index', ['organization' => $orgA->slug]));
        $responseB = $this->actingAs($this->admin, 'admin')
            ->get(route('org.admin.courses.index', ['organization' => $orgB->slug]));

        $responseA->assertStatus(200);
        $responseA->assertSee('Org A Only Course');

        $responseB->assertStatus(200);
        $responseB->assertDontSee('Org A Only Course');
    }

    public function test_accessing_org_b_show_for_course_only_in_org_a_returns_404(): void
    {
        $orgA = Organization::create(['name' => 'Org A', 'slug' => 'org-a', 'is_active' => true]);
        $orgB = Organization::create(['name' => 'Org B', 'slug' => 'org-b', 'is_active' => true]);

        $courseAOnly = Course::create([
            'title' => 'Org A Only Course',
            'slug' => 'org-a-only-course',
            'is_active' => true,
        ]);
        $orgA->courses()->attach($courseAOnly->id);

        $response = $this->actingAs($this->admin, 'admin')
            ->get(route('org.admin.courses.show', [
                'organization' => $orgB->slug,
                'course' => $courseAOnly->id,
            ]));

        $response->assertStatus(404);
    }

    public function test_accessing_org_b_edit_for_course_only_in_org_a_returns_404(): void
    {
        $orgA = Organization::create(['name' => 'Org A', 'slug' => 'org-a', 'is_active' => true]);
        $orgB = Organization::create(['name' => 'Org B', 'slug' => 'org-b', 'is_active' => true]);

        $courseAOnly = Course::create([
            'title' => 'Org A Only Course',
            'slug' => 'org-a-only-course',
            'is_active' => true,
        ]);
        $orgA->courses()->attach($courseAOnly->id);

        $response = $this->actingAs($this->admin, 'admin')
            ->get(route('org.admin.courses.edit', [
                'organization' => $orgB->slug,
                'course' => $courseAOnly->id,
            ]));

        $response->assertStatus(404);
    }

    public function test_creating_course_in_org_scope_attaches_to_org(): void
    {
        $org = Organization::create(['name' => 'Test Org', 'slug' => 'test-org', 'is_active' => true]);

        $response = $this->actingAs($this->admin, 'admin')
            ->post(route('org.admin.courses.store', ['organization' => $org->slug]), [
                'title' => 'New Course',
                'slug' => 'new-course',
                'is_active' => true,
                '_token' => csrf_token(),
            ]);

        $response->assertRedirect();
        $course = Course::where('slug', 'new-course')->first();
        $this->assertNotNull($course);
        $this->assertTrue($org->courses()->where('courses.id', $course->id)->exists());
    }
}
