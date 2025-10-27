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
        $lessons = Lesson::active()
            ->where('is_active', true)
            ->where('grade_level', $gradeLevel)
            ->orderBy('session_number')
            ->orderBy('created_at')
            ->get();

        return view('student.grade', compact('lessons', 'gradeLevel'));
    }
}