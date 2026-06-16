<?php

namespace Tests\Unit;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\Organization;
use App\Services\LessonSessionGrouper;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LessonSessionGrouperTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_groups_lessons_by_session_number_with_parts_sorted(): void
    {
        $course = Course::create([
            'title' => 'Test Course',
            'slug' => 'test-course-grouping',
            'is_active' => true,
            'sort_order' => 99,
        ]);

        $partB = Lesson::create([
            'course_id' => $course->id,
            'title' => 'Family Practice',
            'slug' => 'family-practice',
            'session_number' => 4,
            'part_number' => 2,
            'session_title' => 'Session 4 - Part B',
            'is_active' => true,
        ]);

        $partA = Lesson::create([
            'course_id' => $course->id,
            'title' => 'Family Vocabulary',
            'slug' => 'family-vocab',
            'session_number' => 4,
            'part_number' => 1,
            'session_title' => 'Session 4 - Part A',
            'is_active' => true,
        ]);

        $groups = LessonSessionGrouper::group(collect([$partB, $partA]));

        $this->assertCount(1, $groups['sessions']);
        $this->assertSame(4, $groups['sessions'][0]['session_number']);
        $this->assertSame('Session 4', $groups['sessions'][0]['label']);
        $this->assertSame('Family Vocabulary', $groups['sessions'][0]['lessons'][0]->title);
        $this->assertSame('Family Practice', $groups['sessions'][0]['lessons'][1]->title);
        $this->assertTrue($groups['review']->isEmpty());
        $this->assertTrue($groups['ungrouped']->isEmpty());
    }

    public function test_separates_review_and_ungrouped_lessons(): void
    {
        $course = Course::create([
            'title' => 'Mixed Course',
            'slug' => 'mixed-course-grouping',
            'is_active' => true,
            'sort_order' => 100,
        ]);

        $sessionLesson = Lesson::create([
            'course_id' => $course->id,
            'title' => 'Session Lesson',
            'slug' => 'session-lesson',
            'session_number' => 1,
            'part_number' => 1,
            'is_active' => true,
        ]);

        $reviewLesson = Lesson::create([
            'course_id' => $course->id,
            'title' => 'Review All',
            'slug' => 'review-all',
            'is_review' => true,
            'session_number' => 1,
            'is_active' => true,
        ]);

        $ungroupedLesson = Lesson::create([
            'course_id' => $course->id,
            'title' => 'Standalone',
            'slug' => 'standalone-lesson',
            'is_active' => true,
        ]);

        $groups = LessonSessionGrouper::group(collect([$sessionLesson, $reviewLesson, $ungroupedLesson]));

        $this->assertCount(1, $groups['sessions']);
        $this->assertCount(1, $groups['review']);
        $this->assertCount(1, $groups['ungrouped']);
        $this->assertSame('Review All', $groups['review']->first()->title);
        $this->assertSame('Standalone', $groups['ungrouped']->first()->title);
    }

    public function test_part_label_maps_numbers_to_letters(): void
    {
        $lessonA = new Lesson(['part_number' => 1]);
        $lessonB = new Lesson(['part_number' => 2]);
        $lessonC = new Lesson(['part_number' => 9]);

        $this->assertSame('Part A', $lessonA->partLabel());
        $this->assertSame('Part B', $lessonB->partLabel());
        $this->assertSame('Part 9', $lessonC->partLabel());
    }

    public function test_strip_part_suffix_removes_trailing_part_label(): void
    {
        $this->assertSame(
            'Session 4',
            LessonSessionGrouper::stripPartSuffix('Session 4 - Part A')
        );
        $this->assertSame(
            'My Family',
            LessonSessionGrouper::stripPartSuffix('My Family - Part B')
        );
    }
}
