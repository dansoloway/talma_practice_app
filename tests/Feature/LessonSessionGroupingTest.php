<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\Organization;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LessonSessionGroupingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_student_course_page_renders_session_accordion(): void
    {
        $org = Organization::where('slug', 'default')->firstOrFail();

        $course = Course::create([
            'title' => 'Accordion Test Course',
            'slug' => 'accordion-test-course',
            'is_active' => true,
            'sort_order' => 200,
        ]);

        $org->courses()->attach($course->id, ['is_org_wide' => true]);

        Lesson::create([
            'course_id' => $course->id,
            'title' => 'Pets Vocabulary',
            'slug' => 'pets-vocab',
            'session_number' => 2,
            'part_number' => 1,
            'session_title' => 'Session 2 - Part A',
            'is_active' => true,
        ]);

        Lesson::create([
            'course_id' => $course->id,
            'title' => 'Pets Practice',
            'slug' => 'pets-practice',
            'session_number' => 2,
            'part_number' => 2,
            'session_title' => 'Session 2 - Part B',
            'is_active' => true,
        ]);

        $response = $this->get(route('student.course', $course->slug));

        $response->assertOk();
        $response->assertSee('Session 2');
        $response->assertSee('Pets Vocabulary');
        $response->assertSee('Pets Practice');
        $response->assertSee('Part A');
        $response->assertSee('Part B');
        $response->assertSee('data-session-accordion="session-2"', false);
    }

    public function test_admin_course_show_renders_session_accordion(): void
    {
        $admin = \App\Models\User::where('role', 'admin')->firstOrFail();

        $course = Course::create([
            'title' => 'Admin Accordion Course',
            'slug' => 'admin-accordion-course',
            'is_active' => true,
            'sort_order' => 201,
        ]);

        Lesson::create([
            'course_id' => $course->id,
            'title' => 'Intro Part A',
            'slug' => 'intro-part-a',
            'session_number' => 1,
            'part_number' => 1,
            'session_title' => 'Session 1 - Part A',
            'is_active' => true,
        ]);

        Lesson::create([
            'course_id' => $course->id,
            'title' => 'Intro Part B',
            'slug' => 'intro-part-b',
            'session_number' => 1,
            'part_number' => 2,
            'session_title' => 'Session 1 - Part B',
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin, 'admin')
            ->get(route('admin.courses.show', $course));

        $response->assertOk();
        $response->assertSee('Session 1');
        $response->assertSee('Intro Part A');
        $response->assertSee('Intro Part B');
        $response->assertSee('Part A');
        $response->assertSee('Part B');
    }
}
