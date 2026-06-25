<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\Organization;
use App\Services\CourseAccess;
use Illuminate\Http\RedirectResponse;

trait GuardsRestrictedCourseAccess
{
    /**
     * Gate legacy (non-org-scoped) routes for courses that require login.
     *
     * @return RedirectResponse|Organization|null Redirect when guest; org when restricted access granted; null for public courses
     */
    protected function ensureLegacyCourseAccess(Lesson $lesson): RedirectResponse|Organization|null
    {
        $lesson->loadMissing('course');
        $course = $lesson->course;
        if (!$course) {
            abort(404);
        }

        return $this->ensureLegacyCourseAccessForCourse($course);
    }

    /**
     * @return RedirectResponse|Organization|null
     */
    protected function ensureLegacyCourseAccessForCourse(Course $course): RedirectResponse|Organization|null
    {
        $courseAccess = app(CourseAccess::class);

        if (!$courseAccess->courseRequiresAuth($course)) {
            return null;
        }

        $tenantOrg = $courseAccess->primaryTenantOrgForCourse($course);
        if (!$tenantOrg) {
            abort(403, 'You do not have access to this course.');
        }

        $user = auth('admin')->check() ? auth('admin')->user() : null;

        if (!$user) {
            return redirect()->guest(route('org.student.login', $tenantOrg));
        }

        if (!$courseAccess->canAccessCourse($user, $course, $tenantOrg)) {
            abort(403, 'You do not have access to this course.');
        }

        return $tenantOrg;
    }
}
