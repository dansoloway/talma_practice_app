<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Lesson;
use Illuminate\Http\Request;

class StudentController extends Controller
{
    /**
     * Show the student homepage with course selection
     */
    public function index()
    {
        // Get all active courses with their lesson counts
        $courses = Course::active()
            ->ordered()
            ->withCount(['lessons' => function ($query) {
                $query->where('is_active', true)->whereNull('archived_at');
            }])
            ->get();

        return view('student.index', compact('courses'));
    }

    /**
     * Show lessons for a specific course
     */
    public function course(Request $request, Course $course)
    {
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

        return view('student.course', compact('course', 'lessons', 'sessionNumbers', 'partNumbers'));
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
            ->get();
        
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

        return view('student.grade', compact('lessons', 'gradeLevel', 'sessionNumbers', 'partNumbers'));
    }

    /**
     * Update the order of lessons within a grade level (admin only)
     */
    public function updateLessonOrder(Request $request, $gradeLevel)
    {
        // Check if admin is authenticated
        if (!session('admin_authenticated')) {
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