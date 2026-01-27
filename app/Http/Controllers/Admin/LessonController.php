<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Lesson;
use App\Models\GrammarSet;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class LessonController extends Controller
{
    /**
     * Display a listing of all lessons.
     */
    public function index(Request $request)
    {
        // Determine if we should show archived lessons
        $showArchived = $request->boolean('view_archived');
        
        if ($showArchived) {
            $query = Lesson::archived();
        } else {
            $query = Lesson::active();
        }
        
        // Filter by grade level
        if ($request->filled('grade_level')) {
            $query->where('grade_level', $request->grade_level);
        }
        
        // Filter by session number
        if ($request->filled('session_number')) {
            $query->where('session_number', $request->session_number);
        }
        
        // Filter by search text (title, session_title, slug)
        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('title', 'LIKE', "%{$searchTerm}%")
                  ->orWhere('session_title', 'LIKE', "%{$searchTerm}%")
                  ->orWhere('slug', 'LIKE', "%{$searchTerm}%");
            });
        }
        
        // Handle sorting - default to updated_at desc (most recently updated first)
        $sortBy = $request->get('sort_by', 'updated_at');
        $sortDir = $request->get('sort_dir', 'desc');
        
        // Validate sort direction
        if (!in_array($sortDir, ['asc', 'desc'])) {
            $sortDir = 'desc'; // Default to desc for updated_at
        }
        
        // Apply sorting based on column
        switch ($sortBy) {
            case 'title':
                $query->orderBy('title', $sortDir);
                break;
            case 'slug':
                $query->orderBy('slug', $sortDir);
                break;
            case 'updated_at':
                $query->orderBy('updated_at', $sortDir);
                break;
            case 'session_number':
                // Session number sorting: session number, then part number, then created_at
                $query->orderBy('session_number', $sortDir)
                      ->orderBy('part_number', 'asc')
                      ->orderBy('created_at', 'asc');
                break;
            default:
                // Default sorting: updated_at desc (most recently updated first)
                $query->orderBy('updated_at', 'desc');
                break;
        }
        
        $lessons = $query->with(['prompts', 'vocabulary', 'matchingGames', 'flashcardGames', 'parts'])->get();
        
        // Get available grade levels for filter dropdown (from active lessons)
        $gradeLevels = Lesson::whereNull('archived_at')
            ->whereNotNull('grade_level')
            ->distinct()
            ->orderBy('grade_level')
            ->pluck('grade_level');
        
        // Get available session numbers for filter dropdown (from active lessons)
        $sessionNumbers = Lesson::whereNull('archived_at')
            ->whereNotNull('session_number')
            ->distinct()
            ->orderBy('session_number')
            ->pluck('session_number');
        
        return view('admin.lessons.index', compact('lessons', 'gradeLevels', 'sessionNumbers', 'sortBy', 'sortDir', 'showArchived'));
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
            'grade_level' => 'nullable|string|max:20',
            'session_number' => 'nullable|integer|min:1',
            'part_number' => 'nullable|integer|min:1|max:8',
            'session_title' => 'nullable|string|max:255',
            'is_active' => 'boolean',
            'sort_order' => 'nullable|integer',
        ]);
        
        // Always set instructions to null
        $validated['instructions'] = null;

        // Auto-generate slug if not provided
        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['title']);
        } else {
            // Normalize manually entered slug
            $validated['slug'] = Str::slug($validated['slug']);
        }
        
        // Ensure slug is unique
        $baseSlug = $validated['slug'];
        $counter = 1;
        while (Lesson::where('slug', $validated['slug'])->exists()) {
            $validated['slug'] = $baseSlug . '-' . $counter;
            $counter++;
        }

        // If sort_order not provided, is null, empty, or is 0, set it to be last (highest + 1) for the same grade level
        if (empty($validated['sort_order']) || $validated['sort_order'] == 0) {
            $query = Lesson::query();
            
            // If grade_level is set, find max sort_order for that grade level
            if (!empty($validated['grade_level'])) {
                $query->where('grade_level', $validated['grade_level']);
            }
            
            $maxSortOrder = $query->max('sort_order') ?? 0;
            $validated['sort_order'] = $maxSortOrder + 1;
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
        $lesson->load(['prompts', 'vocabulary', 'matchingGames', 'flashcardGames', 'spellingGames', 'sentenceBuilderGames', 'trueFalseQuestions', 'grammarSets.grammarConcepts', 'clauseExercises']);
        
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
            'grade_level' => 'nullable|string|max:20',
            'session_number' => 'nullable|integer|min:1',
            'part_number' => 'nullable|integer|min:1|max:8',
            'session_title' => 'nullable|string|max:255',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ]);
        
        // Always set instructions to null
        $validated['instructions'] = null;
        
        // Normalize slug to ensure proper formatting
        $validated['slug'] = Str::slug($validated['slug']);
        
        // Ensure slug is unique
        $baseSlug = $validated['slug'];
        $counter = 1;
        while (Lesson::where('slug', $validated['slug'])->where('id', '!=', $lesson->id)->exists()) {
            $validated['slug'] = $baseSlug . '-' . $counter;
            $counter++;
        }

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
            'activity_type' => 'required|in:prompt,prompts,matching,flashcard,spelling',
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
                case 'spelling':
                    $activity = $lesson->spellingGames()->findOrFail($validated['activity_id']);
                    $activity->delete();
                    break;
                case 'sentence_builder':
                    $activity = $lesson->sentenceBuilderGames()->findOrFail($validated['activity_id']);
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

    /**
     * Attach a grammar set to a lesson.
     */
    public function attachGrammarSet(Request $request, Lesson $lesson)
    {
        $validated = $request->validate([
            'grammar_set_id' => 'required|exists:grammar_sets,id',
        ]);

        $grammarSet = GrammarSet::findOrFail($validated['grammar_set_id']);
        
        // Check if already attached
        if ($lesson->grammarSets()->where('grammar_set_id', $grammarSet->id)->exists()) {
            return redirect()
                ->route('admin.lessons.manage', $lesson)
                ->with('error', 'This grammar set is already associated with this lesson.');
        }

        $lesson->grammarSets()->attach($grammarSet->id);

        return redirect()
            ->route('admin.lessons.manage', $lesson)
            ->with('success', "Grammar set '{$grammarSet->title}' has been associated with this lesson.");
    }

    /**
     * Detach a grammar set from a lesson.
     */
    public function detachGrammarSet(Lesson $lesson, GrammarSet $grammarSet)
    {
        $lesson->grammarSets()->detach($grammarSet->id);

        return redirect()
            ->route('admin.lessons.manage', $lesson)
            ->with('success', "Grammar set '{$grammarSet->title}' has been removed from this lesson.");
    }

    /**
     * Update the cover image for a lesson.
     */
    public function updateCoverImage(Request $request, Lesson $lesson)
    {
        $validated = $request->validate([
            'cover_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'cover_image_source' => 'nullable|string',
        ]);

        // Option 1: Use vocabulary image (copy path)
        if ($request->filled('cover_image_source')) {
            $sourcePath = $request->input('cover_image_source');
            
            // Verify the path exists and is from vocabulary
            if (Storage::disk('public')->exists($sourcePath)) {
                // Delete old cover image if it exists and is different
                if ($lesson->cover_image_path && $lesson->cover_image_path !== $sourcePath) {
                    // Only delete if it's not a vocabulary image (to avoid deleting vocab images)
                    if (strpos($lesson->cover_image_path, 'images/vocabulary/') === false) {
                        Storage::disk('public')->delete($lesson->cover_image_path);
                    }
                }
                
                $lesson->update(['cover_image_path' => $sourcePath]);
                
                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json([
                        'success' => true,
                        'message' => 'Cover image updated successfully',
                        'cover_image_url' => $lesson->cover_image_url,
                    ]);
                }
                
                return redirect()
                    ->route('admin.lessons.manage', $lesson)
                    ->with('success', 'Cover image updated successfully!');
            }
        }

        // Option 2: Upload new image
        if ($request->hasFile('cover_image')) {
            $image = $request->file('cover_image');
            
            // Delete old cover image if it exists
            if ($lesson->cover_image_path) {
                // Only delete if it's not a vocabulary image (to avoid deleting vocab images)
                if (strpos($lesson->cover_image_path, 'images/vocabulary/') === false) {
                    Storage::disk('public')->delete($lesson->cover_image_path);
                }
            }
            
            // Use secure filename generation
            $filename = \App\Services\FileUploadSecurity::generateSecureFilename($image, 'lesson_cover');
            $path = $image->storeAs('images/lessons', $filename, 'public');
            $validated['cover_image_path'] = 'images/lessons/' . $filename;
            
            $lesson->update(['cover_image_path' => $validated['cover_image_path']]);
            
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Cover image uploaded successfully',
                    'cover_image_url' => $lesson->cover_image_url,
                ]);
            }
            
            return redirect()
                ->route('admin.lessons.manage', $lesson)
                ->with('success', 'Cover image uploaded successfully!');
        }

        // No valid input provided
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => false,
                'message' => 'No valid cover image provided',
            ], 400);
        }
        
        return redirect()
            ->route('admin.lessons.manage', $lesson)
            ->with('error', 'No valid cover image provided.');
    }

    /**
     * Remove the cover image from a lesson.
     */
    public function removeCoverImage(Request $request, Lesson $lesson)
    {
        // Delete the file if it exists and is not a vocabulary image
        if ($lesson->cover_image_path) {
            // Only delete if it's not a vocabulary image (to avoid deleting vocab images)
            if (strpos($lesson->cover_image_path, 'images/vocabulary/') === false) {
                Storage::disk('public')->delete($lesson->cover_image_path);
            }
        }
        
        $lesson->update(['cover_image_path' => null]);
        
        return redirect()
            ->route('admin.lessons.manage', $lesson)
            ->with('success', 'Cover image removed successfully!');
    }
}

