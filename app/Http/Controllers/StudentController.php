<?php

namespace App\Http\Controllers;

use App\Models\Lesson;
use Illuminate\Http\Request;

class StudentController extends Controller
{
    /**
     * Show the student homepage with grade level selection
     */
    public function index()
    {
        // Get all available grade levels from active, non-archived lessons
        $gradeLevels = Lesson::active()
            ->where('is_active', true)
            ->whereNotNull('grade_level')
            ->distinct()
            ->orderBy('grade_level')
            ->pluck('grade_level');

        return view('student.index', compact('gradeLevels'));
    }

    /**
     * Show lessons for a specific grade level
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
        
        $lessons = $query->orderBy('sort_order')
            ->orderBy('session_number')
            ->orderBy('part_number')
            ->orderBy('created_at')
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