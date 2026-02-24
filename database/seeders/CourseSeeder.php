<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\Lesson;
use Illuminate\Database\Seeder;

class CourseSeeder extends Seeder
{
    /**
     * Create courses from distinct grade levels in lessons, and assign lessons to courses.
     * Idempotent: mirrors migrate_grade_levels_to_courses logic for fresh installs
     * where lessons are seeded after migrations (so that migration yields 0 courses).
     */
    public function run(): void
    {
        $gradeLevels = Lesson::whereNotNull('grade_level')
            ->distinct()
            ->pluck('grade_level')
            ->sort()
            ->values();

        if ($gradeLevels->isEmpty()) {
            $this->command->info('No lessons with grade_level found; creating default Grade 1 course.');
            $gradeLevels = collect(['1']);
        }

        foreach ($gradeLevels as $gradeLevel) {
            $slug = "grade-{$gradeLevel}";

            $course = Course::firstOrCreate(
                ['slug' => $slug],
                [
                    'title' => "Grade {$gradeLevel}",
                    'description' => "Lessons for Grade {$gradeLevel}",
                    'is_active' => true,
                    'sort_order' => ($gradeLevel === 'K' || $gradeLevel === 'k') ? 0 : (int) $gradeLevel,
                ]
            );

            Lesson::where('grade_level', $gradeLevel)
                ->whereNull('course_id')
                ->update(['course_id' => $course->id]);
        }

        $courseCount = Course::count();
        $this->command->info("Created/found {$courseCount} course(s) from grade levels.");
    }
}
