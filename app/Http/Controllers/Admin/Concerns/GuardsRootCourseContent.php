<?php

namespace App\Http\Controllers\Admin\Concerns;

use App\Models\Course;
use App\Models\Lesson;
use Illuminate\Support\Facades\Auth;

trait GuardsRootCourseContent
{
    /**
     * Abort 403 if the course (or lesson's course) is Root-owned and user is not a global admin.
     * Root course content may only be edited by global admins.
     */
    protected function guardRootCourseContent(Course|Lesson|null $courseOrLesson): void
    {
        $course = $courseOrLesson instanceof Lesson ? $courseOrLesson->course : $courseOrLesson;
        if (!$course) {
            return;
        }
        if ($course->isRootOwned() && Auth::guard('admin')->user()?->role !== 'admin') {
            abort(403, 'Only global admins can edit Root-owned course content.');
        }
    }
}
