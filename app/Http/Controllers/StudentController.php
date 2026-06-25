<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\GuardsRestrictedCourseAccess;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Organization;
use App\Services\CourseAccess;
use App\Services\LessonSessionGrouper;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class StudentController extends Controller
{
    use GuardsRestrictedCourseAccess;

    protected CourseAccess $courseAccess;

    public function __construct(CourseAccess $courseAccess)
    {
        $this->courseAccess = $courseAccess;
    }

    /**
     * Show the student homepage with course selection, organized by organization.
     * Root / and /lessons: show all accessible orgs with their courses.
     * /o/{organization}/: show that org's courses.
     * Default org displays as "TALMA Community Resources".
     */
    public function index(?Organization $organization = null)
    {
        $user = auth('admin')->check() ? auth('admin')->user() : null;

        $orgs = $organization
            ? collect([$organization])
            : $this->getAccessibleOrgs($user);

        $orgsWithCourses = $orgs->filter(fn ($org) => $org->is_active)->map(function ($org) use ($user) {
            $courses = $this->courseAccess->accessibleCourses($user, $org)
                ->withCount(['lessons' => fn ($q) => $q->where('is_active', true)->whereNull('archived_at')])
                ->get();
            return ['org' => $org, 'courses' => $courses];
        })->filter(fn ($row) => $row['courses']->isNotEmpty());

        return view('student.index', ['orgsWithCourses' => $orgsWithCourses]);
    }

    /**
     * Get orgs the user can access: open orgs for everyone; restricted orgs if user is member.
     */
    protected function getAccessibleOrgs($user)
    {
        $query = Organization::where('is_active', true)
            ->where(function ($q) use ($user) {
                $q->where('access_mode', 'open');
                if ($user) {
                    $q->orWhereIn('id', $user->organizations()->pluck('organizations.id'));
                }
            });

        return $query->orderByRaw("CASE WHEN slug = 'default' THEN 0 ELSE 1 END")
            ->orderBy('name')
            ->get();
    }

    /**
     * Show lessons for a specific course.
     * Legacy route: /courses/{course}. Org route: /o/{organization}/courses/{course}.
     */
    public function course(Request $request)
    {
        $courseParam = $request->route('course');
        $course = $courseParam instanceof Course
            ? $courseParam
            : Course::where('slug', $courseParam)->firstOrFail();
        $organization = $request->route('organization');

        if ($organization instanceof Organization) {
            $org = $organization;
        } else {
            $gate = $this->ensureLegacyCourseAccessForCourse($course);
            if ($gate instanceof RedirectResponse) {
                return $gate;
            }
            $org = $gate instanceof Organization
                ? $gate
                : Organization::where('slug', 'default')->where('is_active', true)->firstOrFail();
        }

        $user = auth('admin')->check() ? auth('admin')->user() : null;

        if (!$this->courseAccess->canAccessCourse($user, $course, $org)) {
            abort(403, 'You do not have access to this course.');
        }

        $query = $course->activeLessons();
        
        // Filter by session number (which is now the order within course)
        if ($request->filled('session_number')) {
            $query->where('session_number', $request->session_number);
        }
        
        // Filter by part number
        if ($request->filled('part_number')) {
            $query->where('part_number', $request->part_number);
        }
        
        // Filter by search text (title, slug)
        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('title', 'LIKE', "%{$searchTerm}%")
                  ->orWhere('slug', 'LIKE', "%{$searchTerm}%");
            });
        }
        
        $lessons = $query->orderBy('session_number', 'asc')
            ->orderBy('part_number', 'asc')
            ->orderBy('created_at', 'asc')
            ->with(['vocabulary', 'prompts', 'matchingGames', 'flashcardGames', 'reviewSources'])
            ->get();
        
        // For review lessons, load vocabulary from source lessons for display
        foreach ($lessons as $lesson) {
            if ($lesson->is_review) {
                $lesson->setRelation('vocabulary', $lesson->getVocabularyForGames());
            }
        }
        
        // Get available session numbers for filter dropdown
        $sessionNumbers = $course->activeLessons()
            ->whereNotNull('session_number')
            ->select('session_number')
            ->distinct()
            ->reorder()
            ->orderBy('session_number')
            ->pluck('session_number');
        
        // Get available part numbers for filter dropdown
        $partNumbers = $course->activeLessons()
            ->whereNotNull('part_number')
            ->select('part_number')
            ->distinct()
            ->reorder()
            ->orderBy('part_number')
            ->pluck('part_number');

        $lessonGroups = LessonSessionGrouper::group($lessons);

        return view('student.course', compact('course', 'lessons', 'lessonGroups', 'sessionNumbers', 'partNumbers', 'org'));
    }

    /**
     * Show lessons for a specific grade level (kept for backward compatibility)
     * @deprecated Use course() instead
     */
    public function grade(Request $request, $gradeLevel)
    {
        $query = Lesson::active()
            ->where('is_active', true)
            ->where('grade_level', $gradeLevel);
        
        // Filter by session number
        if ($request->filled('session_number')) {
            $query->where('session_number', $request->session_number);
        }
        
        // Filter by part number
        if ($request->filled('part_number')) {
            $query->where('part_number', $request->part_number);
        }
        
        // Filter by search text (title, slug)
        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('title', 'LIKE', "%{$searchTerm}%")
                  ->orWhere('slug', 'LIKE', "%{$searchTerm}%");
            });
        }
        
        $lessons = $query->orderBy('session_number', 'asc')
            ->orderBy('part_number', 'asc')
            ->orderBy('created_at', 'asc')
            ->with(['vocabulary', 'prompts', 'matchingGames', 'flashcardGames'])
            ->get();

        $lessonGroups = LessonSessionGrouper::group($lessons);
        
        // Get available session numbers for filter dropdown
        $sessionNumbers = Lesson::active()
            ->where('is_active', true)
            ->where('grade_level', $gradeLevel)
            ->whereNotNull('session_number')
            ->distinct()
            ->orderBy('session_number')
            ->pluck('session_number');
        
        // Get available part numbers for filter dropdown
        $partNumbers = Lesson::active()
            ->where('is_active', true)
            ->where('grade_level', $gradeLevel)
            ->whereNotNull('part_number')
            ->distinct()
            ->orderBy('part_number')
            ->pluck('part_number');

        return view('student.grade', compact('lessons', 'lessonGroups', 'gradeLevel', 'sessionNumbers', 'partNumbers'));
    }

    /**
     * Update the order of lessons within a grade level (admin only)
     */
    public function updateLessonOrder(Request $request, $gradeLevel)
    {
        // Check if admin is authenticated
        if (!auth('admin')->check()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $request->validate([
            'lesson_ids' => 'required|array',
            'lesson_ids.*' => 'exists:lessons,id',
        ]);

        $lessonIds = $request->input('lesson_ids');

        // Verify all lessons belong to the specified grade level
        $lessons = Lesson::where('grade_level', $gradeLevel)
            ->whereIn('id', $lessonIds)
            ->get();

        if ($lessons->count() !== count($lessonIds)) {
            return response()->json(['error' => 'Invalid lesson IDs'], 400);
        }

        // Update sort_order for each lesson
        foreach ($lessonIds as $index => $lessonId) {
            Lesson::where('id', $lessonId)->update(['sort_order' => $index + 1]);
        }

        return response()->json(['success' => true, 'message' => 'Lesson order updated successfully']);
    }
}