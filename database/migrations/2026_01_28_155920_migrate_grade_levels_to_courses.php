<?php

use App\Models\Course;
use App\Models\Lesson;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Get all distinct grade levels from lessons
        $gradeLevels = Lesson::whereNotNull('grade_level')
            ->distinct()
            ->pluck('grade_level')
            ->sort();

        foreach ($gradeLevels as $gradeLevel) {
            $slug = "grade-{$gradeLevel}";
            
            // Check if course already exists
            $course = Course::where('slug', $slug)->first();
            
            if (!$course) {
                // Convert grade level to integer for sort_order (K = 0, 1 = 1, etc.)
                $sortOrder = ($gradeLevel === 'K' || $gradeLevel === 'k') ? 0 : (int)$gradeLevel;
                
                // Create a course for this grade level
                $course = Course::create([
                    'title' => "Grade {$gradeLevel}",
                    'slug' => $slug,
                    'description' => "Lessons for Grade {$gradeLevel}",
                    'is_active' => true,
                    'sort_order' => $sortOrder,
                ]);
            }

            // Assign all lessons with this grade level to the course (if not already assigned)
            Lesson::where('grade_level', $gradeLevel)
                ->whereNull('course_id')
                ->update(['course_id' => $course->id]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove course_id from all lessons
        Lesson::whereNotNull('course_id')->update(['course_id' => null]);
        
        // Delete all courses (this will cascade delete lessons if foreign key constraint is set)
        // But we're just removing the course_id, not deleting lessons
        Course::truncate();
    }
};
