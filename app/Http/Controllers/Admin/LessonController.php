<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Lesson;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class LessonController extends Controller
{
    /**
     * Display a listing of all lessons.
     */
    public function index()
    {
        $lessons = Lesson::ordered()->get();
        
        return view('admin.lessons.index', compact('lessons'));
    }

    /**
     * Show the form for creating a new lesson.
     */
    public function create()
    {
        return view('admin.lessons.create');
    }

    /**
     * Store a newly created lesson.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:lessons,slug',
            'instructions' => 'nullable|string',
            'grade_level' => 'nullable|string|max:20',
            'session_number' => 'nullable|integer|min:1',
            'session_title' => 'nullable|string|max:255',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ]);

        // Auto-generate slug if not provided
        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['title']);
        }

        $lesson = Lesson::create($validated);

        return redirect()
            ->route('admin.lessons.show', $lesson)
            ->with('success', 'Lesson created successfully!');
    }

    /**
     * Display the specified lesson with its activities (student preview).
     */
    public function show(Lesson $lesson)
    {
        $lesson->load(['prompts', 'vocabulary', 'matchingGames', 'flashcardGames']);
        
        return view('admin.lessons.show', compact('lesson'));
    }

    /**
     * Display comprehensive lesson management page.
     */
    public function manage(Lesson $lesson)
    {
        $lesson->load(['prompts', 'vocabulary', 'matchingGames', 'flashcardGames']);
        
        return view('admin.lessons.manage', compact('lesson'));
    }

    /**
     * Show the form for editing the lesson.
     * Redirects to the comprehensive manage page.
     */
    public function edit(Lesson $lesson)
    {
        return redirect()->route('admin.lessons.manage', $lesson);
    }

    /**
     * Update the specified lesson.
     */
    public function update(Request $request, Lesson $lesson)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:lessons,slug,' . $lesson->id,
            'instructions' => 'nullable|string',
            'grade_level' => 'nullable|string|max:20',
            'session_number' => 'nullable|integer|min:1',
            'session_title' => 'nullable|string|max:255',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ]);

        $lesson->update($validated);

        return redirect()
            ->route('admin.lessons.manage', $lesson)
            ->with('success', 'Lesson updated successfully!');
    }

    /**
     * Remove the specified lesson.
     */
    public function destroy(Lesson $lesson)
    {
        $lesson->delete();

        return redirect()
            ->route('admin.lessons.index')
            ->with('success', 'Lesson deleted successfully!');
    }

    /**
     * Update the order of activities in a lesson.
     */
    public function updateActivityOrder(Request $request, Lesson $lesson)
    {
        $validated = $request->validate([
            'activities' => 'required|array',
            'activities.*.type' => 'required|in:prompt,matching,flashcard',
            'activities.*.id' => 'required|integer',
            'activities.*.sort_order' => 'required|integer',
        ]);

        try {
            foreach ($validated['activities'] as $activityData) {
                switch ($activityData['type']) {
                    case 'prompt':
                        $activity = $lesson->prompts()->findOrFail($activityData['id']);
                        break;
                    case 'matching':
                        $activity = $lesson->matchingGames()->findOrFail($activityData['id']);
                        break;
                    case 'flashcard':
                        $activity = $lesson->flashcardGames()->findOrFail($activityData['id']);
                        break;
                }

                $activity->update(['sort_order' => $activityData['sort_order']]);
            }

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * Delete an activity from a lesson.
     */
    public function deleteActivity(Request $request, Lesson $lesson)
    {
        $validated = $request->validate([
            'activity_type' => 'required|in:prompt,matching,flashcard',
            'activity_id' => 'required|integer',
        ]);

        try {
            switch ($validated['activity_type']) {
                case 'prompt':
                    $activity = $lesson->prompts()->findOrFail($validated['activity_id']);
                    break;
                case 'matching':
                    $activity = $lesson->matchingGames()->findOrFail($validated['activity_id']);
                    break;
                case 'flashcard':
                    $activity = $lesson->flashcardGames()->findOrFail($validated['activity_id']);
                    break;
            }

            $activity->delete();

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}

