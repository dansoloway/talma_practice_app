<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SummerPracticePalAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\OrganizationSeeder::class);
        $this->seed(\Database\Seeders\RootOrganizationSeeder::class);
    }

    public function test_guest_is_redirected_to_summer_login(): void
    {
        $org = Organization::create([
            'name' => 'Summer Practice Pal',
            'slug' => Organization::SUMMER_PRACTICE_PAL_SLUG,
            'access_mode' => 'restricted',
            'allow_self_registration' => true,
            'is_active' => true,
        ]);

        $response = $this->get(route('org.student.index', $org));

        $response->assertRedirect(route('org.student.login', $org));
    }

    public function test_student_can_register_and_access_org_courses(): void
    {
        $org = Organization::create([
            'name' => 'Summer Practice Pal',
            'slug' => Organization::SUMMER_PRACTICE_PAL_SLUG,
            'access_mode' => 'restricted',
            'allow_self_registration' => true,
            'is_active' => true,
        ]);

        $course = Course::create([
            'title' => 'Summer Practice Pal — Pre-A1',
            'slug' => 'summer-practice-pal-pre-a1',
            'is_active' => true,
        ]);

        $org->courses()->attach($course->id, ['is_org_wide' => true]);

        $response = $this->post(route('org.student.register.submit', $org), [
            'name' => 'Test Learner',
            'email' => 'learner@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect(route('org.student.index', $org));
        $this->assertAuthenticated('admin');

        $user = User::where('email', 'learner@example.com')->first();
        $this->assertTrue($user->isStudent());
        $this->assertTrue($user->isMemberOfOrg($org->id));

        $this->get(route('org.student.index', $org))
            ->assertOk()
            ->assertSee('Summer Practice Pal — Pre-A1');
    }

    public function test_restricted_only_course_requires_auth_on_legacy_route(): void
    {
        $org = Organization::create([
            'name' => 'Summer Practice Pal',
            'slug' => Organization::SUMMER_PRACTICE_PAL_SLUG,
            'access_mode' => 'restricted',
            'allow_self_registration' => true,
            'is_active' => true,
        ]);

        $course = Course::create([
            'title' => 'Summer Practice Pal — Pre-A1',
            'slug' => 'summer-practice-pal-pre-a1',
            'is_active' => true,
        ]);

        $org->courses()->attach($course->id, ['is_org_wide' => true]);

        $lesson = \App\Models\Lesson::create([
            'course_id' => $course->id,
            'title' => 'Test Lesson',
            'slug' => 'summer-test-lesson',
            'is_active' => true,
        ]);

        $response = $this->get(route('lessons.show', $lesson->slug));

        $response->assertRedirect(route('org.student.login', $org));
    }
}
