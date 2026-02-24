<?php

namespace App\Services;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CourseAccess
{
    /**
     * Determine if a user (or guest) can access a course in an organization.
     * Access requires:
     * 1. Course is attached to the org
     * 2. Either organization_course.is_org_wide = true, OR user is in a class in this org that has this course.
     */
    public function canAccessCourse(?User $user, Course $course, Organization $organization): bool
    {
        $pivot = DB::table('organization_course')
            ->where('organization_id', $organization->id)
            ->where('course_id', $course->id)
            ->first();

        if (!$pivot) {
            return false;
        }

        if ($pivot->is_org_wide) {
            return true;
        }

        // Class-only course: user must be in a class that has this course
        if (!$user) {
            return false;
        }

        return DB::table('class_user')
            ->join('class_course', 'class_user.class_id', '=', 'class_course.class_id')
            ->where('class_user.user_id', $user->id)
            ->where('class_course.course_id', $course->id)
            ->whereExists(function ($q) use ($organization) {
                $q->select(DB::raw(1))
                    ->from('classrooms')
                    ->whereColumn('classrooms.id', 'class_user.class_id')
                    ->where('classrooms.organization_id', $organization->id);
            })
            ->exists();
    }

    /**
     * Determine if a user (or guest) can access a lesson in an organization.
     * Lesson must belong to a course that the user can access.
     */
    public function canAccessLesson(?User $user, Lesson $lesson, Organization $organization): bool
    {
        $course = $lesson->course;
        if (!$course) {
            return false;
        }

        return $this->canAccessCourse($user, $course, $organization);
    }

    /**
     * Get courses accessible to the user (or guest) in the given organization.
     */
    public function accessibleCourses(?User $user, Organization $organization)
    {
        $query = Course::query()
            ->whereHas('organizations', fn ($q) => $q->where('organizations.id', $organization->id))
            ->active()
            ->ordered();

        // Org-wide: all courses where is_org_wide = true
        $orgWideCourseIds = DB::table('organization_course')
            ->where('organization_id', $organization->id)
            ->where('is_org_wide', true)
            ->pluck('course_id');

        $classCourseIds = collect();
        if ($user) {
            $classCourseIds = DB::table('class_user')
                ->join('class_course', 'class_user.class_id', '=', 'class_course.class_id')
                ->join('classrooms', 'classrooms.id', '=', 'class_user.class_id')
                ->where('class_user.user_id', $user->id)
                ->where('classrooms.organization_id', $organization->id)
                ->pluck('class_course.course_id');
        }

        $accessibleIds = $orgWideCourseIds->merge($classCourseIds)->unique()->values();

        return $query->whereIn('courses.id', $accessibleIds);
    }
}
