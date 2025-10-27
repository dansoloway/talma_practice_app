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
    public function index(Request $request)
    {
        $query = Lesson::active()->ordered();
        
        // Filter by grade level
        if ($request->filled('grade_level')) {
            $query->where('grade_level', $request->grade_level);
        }
        
        // Filter by search text (title, session_title, instructions)
        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('title', 'LIKE', "%{$searchTerm}%")
                  ->orWhere('session_title', 'LIKE', "%{$searchTerm}%")
                  ->orWhere('instructions', 'LIKE', "%{$searchTerm}%");
            });
        }
        
        $lessons = $query->get();
        
        // Get available grade levels for filter dropdown
        $gradeLevels = Lesson::active()
            ->whereNotNull('grade_level')
            ->distinct()
            ->orderBy('grade_level')
            ->pluck('grade_level');
        
        return view('admin.lessons.index', compact('lessons', 'gradeLevels'));
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
            'activities.*.type' => 'required|in:prompt,prompts,matching,flashcard',
            'activities.*.id' => 'required',
            'activities.*.sort_order' => 'required|integer',
        ]);

        try {
            foreach ($validated['activities'] as $activityData) {
                switch ($activityData['type']) {
                    case 'prompt':
                        $activity = $lesson->prompts()->findOrFail($activityData['id']);
                        $activity->update(['sort_order' => $activityData['sort_order']]);
                        break;
                    case 'prompts':
                        // Update all prompts in this lesson with the new sort order
                        $lesson->prompts()->update(['sort_order' => $activityData['sort_order']]);
                        break;
                    case 'matching':
                        $activity = $lesson->matchingGames()->findOrFail($activityData['id']);
                        $activity->update(['sort_order' => $activityData['sort_order']]);
                        break;
                    case 'flashcard':
                        $activity = $lesson->flashcardGames()->findOrFail($activityData['id']);
                        $activity->update(['sort_order' => $activityData['sort_order']]);
                        break;
                }
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
            'activity_type' => 'required|in:prompt,prompts,matching,flashcard',
            'activity_id' => 'required',
        ]);

        try {
            switch ($validated['activity_type']) {
                case 'prompt':
                    $activity = $lesson->prompts()->findOrFail($validated['activity_id']);
                    $activity->delete();
                    break;
                case 'prompts':
                    if ($validated['activity_id'] === 'all') {
                        // Delete all prompts for this lesson
                        $lesson->prompts()->delete();
                    }
                    break;
                case 'matching':
                    $activity = $lesson->matchingGames()->findOrFail($validated['activity_id']);
                    $activity->delete();
                    break;
                case 'flashcard':
                    $activity = $lesson->flashcardGames()->findOrFail($validated['activity_id']);
                    $activity->delete();
                    break;
            }

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * Archive a lesson.
     */
    public function archive(Lesson $lesson)
    {
        $lesson->archive();
        
        return redirect()->route('admin.lessons.index')
            ->with('success', 'Lesson "' . $lesson->title . '" has been archived.');
    }

    /**
     * Unarchive a lesson.
     */
    public function unarchive(Lesson $lesson)
    {
        $lesson->unarchive();
        
        return redirect()->route('admin.lessons.archived')
            ->with('success', 'Lesson "' . $lesson->title . '" has been unarchived.');
    }

    /**
     * Display archived lessons.
     */
    public function archived()
    {
        $lessons = Lesson::archived()->ordered()->get();
        
        return view('admin.lessons.archived', compact('lessons'));
    }
}

