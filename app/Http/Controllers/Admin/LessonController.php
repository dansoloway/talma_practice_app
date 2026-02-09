<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\GrammarSet;
use App\Models\MatchingGame;
use App\Models\FlashcardGame;
use App\Models\SpellingGame;
use App\Models\SentenceBuilderGame;
use App\Models\ClauseExercise;
use App\Models\TrueFalseGame;
use App\Models\TrueFalseQuestion;
use App\Models\Prompt;
use App\Models\Option;
use App\Models\Vocabulary;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

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
        
        $lessons = $query->with(['prompts', 'vocabulary', 'matchingGames', 'flashcardGames', 'parts', 'course'])
            ->get()
            ->sortBy([
                ['title', 'asc'],
                ['session_number', 'asc'],
                ['part_number', 'asc'],
            ])
            ->values();
        
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
        
        // Get available courses for filter dropdown
        $courses = Course::active()->ordered()->get();
        
        return view('admin.lessons.index', compact('lessons', 'gradeLevels', 'sessionNumbers', 'courses', 'sortBy', 'sortDir', 'showArchived'));
    }

    /**
     * Show the form for creating a new lesson.
     */
    public function create(Request $request)
    {
        $courses = Course::active()->ordered()->get();
        $selectedCourseId = $request->get('course_id');
        
        // Get source lesson IDs if creating a review lesson
        $sourceLessonIds = $request->input('review_source_lessons', []);
        $sourceLessons = [];
        $prefilledData = [];
        
        if (!empty($sourceLessonIds)) {
            $sourceLessons = Lesson::whereIn('id', $sourceLessonIds)->get();
            
            // Pre-fill data from source lessons
            if ($sourceLessons->isNotEmpty()) {
                // Get common grade level (if all source lessons have the same grade)
                $gradeLevels = $sourceLessons->pluck('grade_level')->filter()->unique();
                if ($gradeLevels->count() === 1) {
                    $prefilledData['grade_level'] = $gradeLevels->first();
                }
                
                // Get course_id from source lessons if not already provided
                if (!$selectedCourseId) {
                    $courseIds = $sourceLessons->pluck('course_id')->filter()->unique();
                    if ($courseIds->count() === 1) {
                        $selectedCourseId = $courseIds->first();
                    }
                }
            }
        }
        
        // Filter lessons for the source dropdown - if course_id is set, only show lessons from that course
        $allLessonsQuery = Lesson::active()->whereNull('archived_at');
        if ($selectedCourseId) {
            $allLessonsQuery->where('course_id', $selectedCourseId);
        }
        $allLessons = $allLessonsQuery->orderBy('session_number')->orderBy('part_number')->orderBy('title')->get();
        
        return view('admin.lessons.create', compact('courses', 'selectedCourseId', 'allLessons', 'sourceLessonIds', 'prefilledData'));
    }

    /**
     * Store a newly created lesson.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:lessons,slug',
            'course_id' => 'nullable|exists:courses,id',
            'grade_level' => 'nullable|string|max:20',
            'session_number' => 'nullable|integer|min:1',
            'part_number' => 'nullable|integer|min:1|max:8',
            'session_title' => 'nullable|string|max:255',
            'is_active' => 'boolean',
            'is_review' => 'boolean',
            'review_source_lessons' => 'required_if:is_review,1|array',
            'review_source_lessons.*' => 'exists:lessons,id',
            'review_vocabulary_ids' => 'nullable|string',
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

        // Handle review source lessons
        $reviewSourceLessonIds = $request->input('review_source_lessons', []);
        unset($validated['review_source_lessons']);
        
        // Handle review vocabulary IDs (comma-separated string -> array)
        if (!empty($validated['review_vocabulary_ids'])) {
            $vocabIdsString = $validated['review_vocabulary_ids'];
            $vocabIdsArray = array_filter(array_map('intval', explode(',', $vocabIdsString)));
            
            // Validate vocabulary selection for review lessons
            if (!empty($validated['is_review'])) {
                if (count($vocabIdsArray) < 2) {
                    return redirect()->back()
                        ->withInput()
                        ->withErrors(['review_vocabulary_ids' => 'Please select at least 2 vocabulary words for the review lesson.']);
                }
                if (count($vocabIdsArray) > 30) {
                    return redirect()->back()
                        ->withInput()
                        ->withErrors(['review_vocabulary_ids' => 'Maximum 30 words allowed for review lessons.']);
                }
            }
            
            $validated['review_vocabulary_ids'] = !empty($vocabIdsArray) ? $vocabIdsArray : null;
        } else {
            // For review lessons, vocabulary selection is required
            if (!empty($validated['is_review'])) {
                return redirect()->back()
                    ->withInput()
                    ->withErrors(['review_vocabulary_ids' => 'Please select vocabulary words for the review lesson.']);
            }
            $validated['review_vocabulary_ids'] = null;
        }

        // If this is a review lesson, automatically set session_number and grade_level from source lessons
        if (!empty($validated['is_review']) && !empty($reviewSourceLessonIds)) {
            $sourceLessons = Lesson::whereIn('id', $reviewSourceLessonIds)
                ->where('course_id', $validated['course_id'] ?? null)
                ->orderBy('session_number', 'desc')
                ->orderBy('part_number', 'desc')
                ->get();
            
            if ($sourceLessons->isNotEmpty()) {
                // Auto-fill grade_level if all source lessons have the same grade
                if (empty($validated['grade_level'])) {
                    $gradeLevels = $sourceLessons->pluck('grade_level')->filter()->unique();
                    if ($gradeLevels->count() === 1) {
                        $validated['grade_level'] = $gradeLevels->first();
                    }
                }
                
                $lastSourceLesson = $sourceLessons->first();
                // Set session_number to be after the last source lesson
                // If last source has session_number, use that + 0.1 (or next integer if no decimals)
                if ($lastSourceLesson->session_number) {
                    // Check if there are any lessons with session_number between last source and next integer
                    $nextSession = (int)ceil($lastSourceLesson->session_number) + 1;
                    $validated['session_number'] = $nextSession;
                } else {
                    // If source lessons don't have session numbers, find the max session_number in the course
                    $maxSession = Lesson::where('course_id', $validated['course_id'] ?? null)
                        ->whereNotNull('session_number')
                        ->max('session_number') ?? 0;
                    $validated['session_number'] = $maxSession + 1;
                }
                
                // Auto-generate title if not provided and we have source lessons
                if (empty($validated['title']) || $validated['title'] === 'Review: Lessons 1-2') {
                    $sourceTitles = $sourceLessons->pluck('title')->take(2);
                    if ($sourceLessons->count() === 2) {
                        $validated['title'] = 'Review: ' . $sourceTitles->first() . ' & ' . $sourceTitles->last();
                    } else {
                        $sessionNumbers = $sourceLessons->pluck('session_number')->filter()->sort()->values();
                        if ($sessionNumbers->isNotEmpty()) {
                            $validated['title'] = 'Review: Lessons ' . $sessionNumbers->first() . '-' . $sessionNumbers->last();
                        } else {
                            $validated['title'] = 'Review: ' . $sourceLessons->count() . ' Lessons';
                        }
                    }
                }
            }
        }

        $lesson = Lesson::create($validated);

        // Attach source lessons if this is a review lesson
        if ($lesson->is_review && !empty($reviewSourceLessonIds)) {
            // Order source lessons by their session_number and part_number
            $orderedSourceLessons = Lesson::whereIn('id', $reviewSourceLessonIds)
                ->orderBy('session_number', 'asc')
                ->orderBy('part_number', 'asc')
                ->get();
            
            foreach ($orderedSourceLessons as $index => $sourceLesson) {
                $lesson->reviewSources()->attach($sourceLesson->id, ['order' => $index]);
            }
            
            // Automatically create games for review lessons
            $this->createGamesForReviewLesson($lesson);
            
            // Redirect review lessons directly to manage page since games are already created
            return redirect()
                ->route('admin.lessons.manage', $lesson)
                ->with('success', 'Review lesson created successfully! Games have been automatically created.');
        }

        return redirect()
            ->route('admin.lessons.show', $lesson)
            ->with('success', 'Lesson created successfully!');
    }

    /**
     * Display the specified lesson with its activities (student preview).
     */
    public function show(Lesson $lesson)
    {
        $lesson->load(['prompts', 'matchingGames', 'flashcardGames', 'reviewSources']);
        
        // For review lessons, load vocabulary from source lessons
        if ($lesson->is_review) {
            $lesson->setRelation('vocabulary', $lesson->getVocabularyForGames());
        } else {
            $lesson->load('vocabulary');
        }
        
        return view('admin.lessons.show', compact('lesson'));
    }

    /**
     * Display comprehensive lesson management page.
     */
    public function manage(Lesson $lesson)
    {
        $lesson->load(['course', 'prompts', 'matchingGames', 'flashcardGames', 'spellingGames', 'sentenceBuilderGames', 'trueFalseQuestions', 'grammarSets.grammarConcepts', 'clauseExercises', 'reviewSources']);
        
        // For review lessons, load vocabulary from source lessons
        if ($lesson->is_review) {
            $lesson->setRelation('vocabulary', $lesson->getVocabularyForGames());
        } else {
            $lesson->load('vocabulary');
        }
        
        $courses = Course::active()->ordered()->get();
        $allLessons = Lesson::where('id', '!=', $lesson->id)->active()->orderBy('session_number')->orderBy('part_number')->orderBy('title')->get();
        
        return view('admin.lessons.manage', compact('lesson', 'courses', 'allLessons'));
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
            'course_id' => 'nullable|exists:courses,id',
            'grade_level' => 'nullable|string|max:20',
            'session_number' => 'nullable|integer|min:1',
            'part_number' => 'nullable|integer|min:1|max:8',
            'session_title' => 'nullable|string|max:255',
            'is_active' => 'boolean',
            'is_review' => 'boolean',
            'review_source_lessons' => 'required_if:is_review,1|array',
            'review_source_lessons.*' => 'exists:lessons,id',
            'review_vocabulary_ids' => 'nullable|string',
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

        // Handle review source lessons
        $reviewSourceLessonIds = $request->input('review_source_lessons', []);
        unset($validated['review_source_lessons']);
        
        // Handle review vocabulary IDs (comma-separated string -> array)
        if (!empty($validated['review_vocabulary_ids'])) {
            $vocabIdsString = $validated['review_vocabulary_ids'];
            $vocabIdsArray = array_filter(array_map('intval', explode(',', $vocabIdsString)));
            
            // Validate vocabulary selection for review lessons
            if (!empty($validated['is_review'])) {
                if (count($vocabIdsArray) < 2) {
                    return redirect()->back()
                        ->withInput()
                        ->withErrors(['review_vocabulary_ids' => 'Please select at least 2 vocabulary words for the review lesson.']);
                }
                if (count($vocabIdsArray) > 30) {
                    return redirect()->back()
                        ->withInput()
                        ->withErrors(['review_vocabulary_ids' => 'Maximum 30 words allowed for review lessons.']);
                }
            }
            
            $validated['review_vocabulary_ids'] = !empty($vocabIdsArray) ? $vocabIdsArray : null;
        } else {
            // For review lessons, vocabulary selection is required
            if (!empty($validated['is_review']) && $lesson->is_review) {
                // If updating an existing review lesson, keep existing vocabulary if none provided
                $validated['review_vocabulary_ids'] = $lesson->review_vocabulary_ids;
            } else {
                $validated['review_vocabulary_ids'] = null;
            }
        }

        // If this is a review lesson and session_number wasn't manually set, auto-calculate it
        if (!empty($validated['is_review']) && !empty($reviewSourceLessonIds) && empty($request->input('session_number'))) {
            $sourceLessons = Lesson::whereIn('id', $reviewSourceLessonIds)
                ->where('course_id', $validated['course_id'] ?? $lesson->course_id)
                ->orderBy('session_number', 'desc')
                ->orderBy('part_number', 'desc')
                ->get();
            
            if ($sourceLessons->isNotEmpty()) {
                $lastSourceLesson = $sourceLessons->first();
                if ($lastSourceLesson->session_number) {
                    $nextSession = (int)ceil($lastSourceLesson->session_number) + 1;
                    $validated['session_number'] = $nextSession;
                } else {
                    $maxSession = Lesson::where('course_id', $validated['course_id'] ?? $lesson->course_id)
                        ->whereNotNull('session_number')
                        ->where('id', '!=', $lesson->id)
                        ->max('session_number') ?? 0;
                    $validated['session_number'] = $maxSession + 1;
                }
            }
        }

        $lesson->update($validated);

        // Sync review source lessons if this is a review lesson
        if ($lesson->is_review && !empty($reviewSourceLessonIds)) {
            // Order source lessons by their session_number and part_number
            $orderedSourceLessons = Lesson::whereIn('id', $reviewSourceLessonIds)
                ->orderBy('session_number', 'asc')
                ->orderBy('part_number', 'asc')
                ->get();
            
            $syncData = [];
            foreach ($orderedSourceLessons as $index => $sourceLesson) {
                $syncData[$sourceLesson->id] = ['order' => $index];
            }
            $lesson->reviewSources()->sync($syncData);
        } else {
            // Remove all review sources if not a review lesson
            $lesson->reviewSources()->detach();
        }

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
        // Check if this lesson is used as a source in any review lessons
        $reviewLessons = $lesson->reviewLessons()->whereNull('archived_at')->get();
        
        if ($reviewLessons->isNotEmpty()) {
            $reviewLessonTitles = $reviewLessons->pluck('title')->toArray();
            $reviewCount = $reviewLessons->count();
            
            // Get unique courses that use this lesson in review lessons
            $courses = $reviewLessons->pluck('course_id')->filter()->unique();
            $courseCount = $courses->count();
            
            $message = "Cannot archive lesson \"{$lesson->title}\". ";
            
            if ($courseCount > 1) {
                $message .= "This lesson is used as a source in {$reviewCount} review lesson(s) across {$courseCount} different course(s). ";
            } else {
                $message .= "This lesson is used as a source in {$reviewCount} review lesson(s). ";
            }
            
            $message .= "Please remove it from the review lessons first: " . implode(', ', array_slice($reviewLessonTitles, 0, 3));
            if (count($reviewLessonTitles) > 3) {
                $message .= ' and ' . (count($reviewLessonTitles) - 3) . ' more';
            }
            
            return redirect()->back()
                ->with('error', $message);
        }
        
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

    /**
     * Create matching, flashcard, and spelling games automatically for a review lesson
     * using vocabulary from source lessons.
     */
    private function createGamesForReviewLesson(Lesson $lesson)
    {
        // Get vocabulary from source lessons (filtered by review_vocabulary_ids if set)
        $vocabulary = $lesson->getVocabularyForGames();
        $vocabularyIds = $vocabulary->pluck('id')->toArray();
        
        if (empty($vocabularyIds) || count($vocabularyIds) < 2) {
            Log::info("Skipping game creation for review lesson {$lesson->id}: insufficient vocabulary (need at least 2 words)");
            return;
        }
        
        // Limit matching game to 30 words maximum (matching games don't work well with too many words)
        $matchingGameVocabIds = array_slice($vocabularyIds, 0, 30);
        if (count($vocabularyIds) > 30) {
            Log::info("Limiting matching game vocabulary to 30 words for review lesson {$lesson->id} (had " . count($vocabularyIds) . " words)");
        }

        try {
            // Create Matching Game
            $matchingGameTitle = trim($lesson->title . ' Matching Game 1');
            
            MatchingGame::create([
                'lesson_id' => $lesson->id,
                'title' => $matchingGameTitle,
                'vocabulary_ids' => $matchingGameVocabIds,
                'is_active' => true,
            ]);
            Log::info("Created matching game for review lesson {$lesson->id}");

            // Create Flashcard Game
            $flashcardGameTitle = trim($lesson->title . ' Flashcards 1');
            
            // Determine game types based on vocabulary assets
            $missingImages = Vocabulary::whereIn('id', $vocabularyIds)
                ->where(function($q){ $q->whereNull('image_path')->orWhere('image_path', ''); })
                ->count();
            $missingAudio = Vocabulary::whereIn('id', $vocabularyIds)
                ->whereNull('word_audio_path')
                ->count();
            
            $gameTypes = [];
            if ($missingImages > 0 && $missingAudio > 0) {
                $gameTypes = [];
            } elseif ($missingImages > 0) {
                $gameTypes = ['audio_to_word'];
            } elseif ($missingAudio > 0) {
                $gameTypes = ['image_to_word'];
            } else {
                $gameTypes = ['image_to_word', 'audio_to_word'];
            }
            
            FlashcardGame::create([
                'lesson_id' => $lesson->id,
                'title' => $flashcardGameTitle,
                'vocabulary_ids' => $vocabularyIds,
                'game_types' => $gameTypes,
                'cards_per_game' => min(10, count($vocabularyIds)),
                'is_active' => true,
            ]);
            Log::info("Created flashcard game for review lesson {$lesson->id}");

            // Create Spelling Game
            $spellingGameTitle = trim($lesson->title . ' Spelling Practice 1');
            
            SpellingGame::create([
                'lesson_id' => $lesson->id,
                'title' => $spellingGameTitle,
                'vocabulary_ids' => $vocabularyIds,
                'difficulty' => 'medium',
                'is_active' => true,
            ]);
            Log::info("Created spelling game for review lesson {$lesson->id}");

        } catch (\Exception $e) {
            Log::error("Failed to create games for review lesson {$lesson->id}: " . $e->getMessage());
            // Don't throw - allow lesson creation to succeed even if game creation fails
        }
    }

    /**
     * Get vocabulary from multiple lessons for review lesson creation.
     */
    public function getVocabularyForReview(Request $request)
    {
        $request->validate([
            'lesson_ids' => 'required|array',
            'lesson_ids.*' => 'exists:lessons,id',
        ]);

        $lessonIds = $request->input('lesson_ids');
        $vocabulary = Vocabulary::whereIn('lesson_id', $lessonIds)
            ->where('is_active', true)
            ->orderBy('lesson_id')
            ->orderBy('sort_order')
            ->get(['id', 'lesson_id', 'english_word', 'image_path', 'word_audio_path']);

        return response()->json([
            'vocabulary' => $vocabulary->map(function ($vocab) {
                return [
                    'id' => $vocab->id,
                    'lesson_id' => $vocab->lesson_id,
                    'english_word' => $vocab->english_word,
                    'image_url' => $vocab->image_path ? asset('storage/' . $vocab->image_path) : null,
                    'has_audio' => !empty($vocab->word_audio_path),
                ];
            }),
        ]);
    }

    /**
     * Combine multiple lessons into a target lesson.
     */
    public function combine(Request $request)
    {
        try {
            $validated = $request->validate([
                'source_lesson_ids' => 'required|array|min:1',
                'source_lesson_ids.*' => 'exists:lessons,id',
                'target_lesson_id' => 'required|exists:lessons,id',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed: ' . implode(', ', $e->errors()),
                'errors' => $e->errors(),
            ], 400);
        }

        $sourceLessonIds = $validated['source_lesson_ids'];
        $targetLessonId = $validated['target_lesson_id'];

        // Validate target is not in source list
        if (in_array($targetLessonId, $sourceLessonIds)) {
            return response()->json([
                'success' => false,
                'message' => 'Target lesson cannot be one of the source lessons.',
            ], 400);
        }

        // Load lessons with necessary relationships
        $targetLesson = Lesson::with([
            'vocabulary',
            'matchingGames',
            'flashcardGames',
            'spellingGames',
            'prompts',
            'clauseExercises',
            'grammarSets',
            'trueFalseGames',
            'sentenceBuilderGames',
        ])->findOrFail($targetLessonId);
        
        $sourceLessons = Lesson::with([
            'vocabulary',
            'prompts.options',
            'clauseExercises',
            'grammarSets',
            'trueFalseGames.questions',
            'sentenceBuilderGames',
        ])->whereIn('id', $sourceLessonIds)
        ->withoutGlobalScopes() // Include archived lessons
        ->get();

        if ($sourceLessons->count() !== count($sourceLessonIds)) {
            $foundIds = $sourceLessons->pluck('id')->toArray();
            $missingIds = array_diff($sourceLessonIds, $foundIds);
            Log::warning('Some source lessons not found', [
                'requested_ids' => $sourceLessonIds,
                'found_ids' => $foundIds,
                'missing_ids' => $missingIds,
            ]);
            return response()->json([
                'success' => false,
                'message' => 'One or more source lessons not found. Missing IDs: ' . implode(', ', $missingIds),
            ], 400);
        }

        // Start transaction for rollback capability
        DB::beginTransaction();

        try {
            // Step 1: Merge Vocabulary (skip duplicates, preserve order)
            $existingVocabWords = $targetLesson->vocabulary()
                ->where('is_active', true)
                ->pluck('english_word')
                ->map(fn($word) => strtolower(trim($word)))
                ->toArray();

            $vocabToMerge = [];
            $sortOrderOffset = $targetLesson->vocabulary()->max('sort_order') ?? 0;

            foreach ($sourceLessons as $sourceLesson) {
                $sourceVocab = $sourceLesson->vocabulary()
                    ->where('is_active', true)
                    ->orderBy('sort_order')
                    ->get();

                foreach ($sourceVocab as $vocab) {
                    $wordKey = strtolower(trim($vocab->english_word));
                    
                    // Skip duplicates
                    if (!in_array($wordKey, $existingVocabWords)) {
                        $vocabToMerge[] = [
                            'vocab' => $vocab,
                            'new_sort_order' => ++$sortOrderOffset,
                        ];
                        $existingVocabWords[] = $wordKey;
                    }
                }
            }

            // Update vocabulary lesson_id
            foreach ($vocabToMerge as $item) {
                $item['vocab']->update([
                    'lesson_id' => $targetLesson->id,
                    'sort_order' => $item['new_sort_order'],
                ]);
            }

            // Step 2: Get all vocabulary IDs for games (including existing target vocab)
            $allVocabIds = $targetLesson->vocabulary()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->pluck('id')
                ->toArray();

            // Step 3: Create Matching Games with even splitting (12 word limit)
            $matchingGameLimit = 12;
            if (count($allVocabIds) > $matchingGameLimit) {
                $numGames = ceil(count($allVocabIds) / $matchingGameLimit);
                $wordsPerGame = ceil(count($allVocabIds) / $numGames);
                
                $existingMatchingCount = $targetLesson->matchingGames()->count();
                
                for ($i = 0; $i < $numGames; $i++) {
                    $chunk = array_slice($allVocabIds, $i * $wordsPerGame, $wordsPerGame);
                    
                    if (!empty($chunk) && count($chunk) >= 2) {
                        MatchingGame::create([
                            'lesson_id' => $targetLesson->id,
                            'title' => trim($targetLesson->title . ' Matching Game ' . ($existingMatchingCount + $i + 1)),
                            'vocabulary_ids' => $chunk,
                            'is_active' => true,
                            'sort_order' => $targetLesson->matchingGames()->max('sort_order') + $i + 1,
                        ]);
                    }
                }
            } elseif (count($allVocabIds) >= 2) {
                // Single matching game if under limit
                if ($targetLesson->matchingGames()->count() === 0) {
                    MatchingGame::create([
                        'lesson_id' => $targetLesson->id,
                        'title' => trim($targetLesson->title . ' Matching Game 1'),
                        'vocabulary_ids' => $allVocabIds,
                        'is_active' => true,
                        'sort_order' => 1,
                    ]);
                }
            }

            // Step 4: Create other games (no limits) - only if they don't exist
            if ($targetLesson->flashcardGames()->count() === 0 && count($allVocabIds) >= 1) {
                FlashcardGame::create([
                    'lesson_id' => $targetLesson->id,
                    'title' => trim($targetLesson->title . ' Flashcards 1'),
                    'vocabulary_ids' => $allVocabIds,
                    'game_types' => ['image_to_text'], // Default game type
                    'is_active' => true,
                    'sort_order' => 1,
                ]);
            }

            if ($targetLesson->spellingGames()->count() === 0 && count($allVocabIds) >= 1) {
                SpellingGame::create([
                    'lesson_id' => $targetLesson->id,
                    'title' => trim($targetLesson->title . ' Spelling Game 1'),
                    'vocabulary_ids' => $allVocabIds,
                    'is_active' => true,
                    'sort_order' => 1,
                ]);
            }

            // Step 5: Import Prompts (as separate games) with their options
            foreach ($sourceLessons as $sourceLesson) {
                $sourcePrompts = $sourceLesson->prompts()->with('options')->orderBy('sort_order')->get();
                $targetPromptSortOrder = $targetLesson->prompts()->max('sort_order') ?? 0;

                foreach ($sourcePrompts as $prompt) {
                    $newPrompt = Prompt::create([
                        'lesson_id' => $targetLesson->id,
                        'part_id' => $targetLesson->getOrCreateDefaultPart()->id,
                        'prompt_text' => $prompt->prompt_text,
                        'template' => $prompt->template,
                        'tts_voice' => $prompt->tts_voice,
                        'prompt_audio_path' => $prompt->prompt_audio_path,
                        'correct_answer' => $prompt->correct_answer,
                        'is_active' => $prompt->is_active,
                        'sort_order' => ++$targetPromptSortOrder,
                    ]);

                    // Import options for this prompt
                    $optionSortOrder = 0;
                    foreach ($prompt->options as $option) {
                        $newPrompt->options()->create([
                            'label' => $option->label,
                            'image_path' => $option->image_path,
                            'word_audio_path' => $option->word_audio_path,
                            'sentence_audio_path' => $option->sentence_audio_path,
                            'is_active' => $option->is_active,
                            'sort_order' => ++$optionSortOrder,
                        ]);
                    }
                }
            }

            // Step 6: Import Clause Exercises
            foreach ($sourceLessons as $sourceLesson) {
                $sourceExercises = $sourceLesson->clauseExercises()->orderBy('sort_order')->get();
                $targetExerciseSortOrder = $targetLesson->clauseExercises()->max('sort_order') ?? 0;

                foreach ($sourceExercises as $exercise) {
                    ClauseExercise::create([
                        'lesson_id' => $targetLesson->id,
                        'grammar_set_id' => $exercise->grammar_set_id,
                        'title' => $exercise->title,
                        'topic' => $exercise->topic,
                        'paragraph_text' => $exercise->paragraph_text,
                        'blanks' => $exercise->blanks,
                        'correct_answers' => $exercise->correct_answers,
                        'blank_positions' => $exercise->blank_positions,
                        'blank_metadata' => $exercise->blank_metadata,
                        'difficulty_level' => $exercise->difficulty_level,
                        'is_active' => $exercise->is_active,
                        'sort_order' => ++$targetExerciseSortOrder,
                    ]);
                }
            }

            // Step 7: Import Grammar Sets (many-to-many relationship)
            foreach ($sourceLessons as $sourceLesson) {
                $grammarSetIds = $sourceLesson->grammarSets()->pluck('grammar_sets.id')->toArray();
                foreach ($grammarSetIds as $grammarSetId) {
                    if (!$targetLesson->grammarSets()->where('grammar_sets.id', $grammarSetId)->exists()) {
                        $targetLesson->grammarSets()->attach($grammarSetId);
                    }
                }
            }

            // Step 8: Import True/False Games and Questions
            foreach ($sourceLessons as $sourceLesson) {
                $sourceGames = $sourceLesson->trueFalseGames()->orderBy('sort_order')->get();
                $targetGameSortOrder = $targetLesson->trueFalseGames()->max('sort_order') ?? 0;

                foreach ($sourceGames as $game) {
                    $newGame = TrueFalseGame::create([
                        'lesson_id' => $targetLesson->id,
                        'title' => $game->title,
                        'game_version' => $game->game_version,
                        'is_active' => $game->is_active,
                        'sort_order' => ++$targetGameSortOrder,
                    ]);

                    // Import questions for this game
                    $sourceQuestions = $game->questions()->orderBy('sort_order')->get();
                    $questionSortOrder = 0;

                    foreach ($sourceQuestions as $question) {
                        TrueFalseQuestion::create([
                            'lesson_id' => $targetLesson->id,
                            'true_false_game_id' => $newGame->id,
                            'statement' => $question->statement,
                            'is_true' => $question->is_true,
                            'explanation' => $question->explanation,
                            'category' => $question->category,
                            'audio_path' => $question->audio_path,
                            'is_approved' => $question->is_approved,
                            'is_active' => $question->is_active,
                            'sort_order' => ++$questionSortOrder,
                        ]);
                    }
                }
            }

            // Step 9: Import Sentence Builder Games
            foreach ($sourceLessons as $sourceLesson) {
                $sourceGames = $sourceLesson->sentenceBuilderGames()->orderBy('sort_order')->get();
                $targetGameSortOrder = $targetLesson->sentenceBuilderGames()->max('sort_order') ?? 0;

                foreach ($sourceGames as $game) {
                    SentenceBuilderGame::create([
                        'lesson_id' => $targetLesson->id,
                        'title' => $game->title,
                        'vocabulary_ids' => $game->vocabulary_ids,
                        'game_types' => $game->game_types,
                        'cards_per_game' => $game->cards_per_game,
                        'is_active' => $game->is_active,
                        'sort_order' => ++$targetGameSortOrder,
                    ]);
                }
            }

            // Step 10: Archive source lessons
            foreach ($sourceLessons as $sourceLesson) {
                $sourceLesson->archive();
            }

            // Commit transaction
            DB::commit();

            Log::info("Successfully combined lessons", [
                'source_lesson_ids' => $sourceLessonIds,
                'target_lesson_id' => $targetLessonId,
                'vocab_merged' => count($vocabToMerge),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Lessons combined successfully. ' . count($vocabToMerge) . ' vocabulary items merged, ' . count($sourceLessons) . ' source lesson(s) archived.',
            ]);

        } catch (\Exception $e) {
            // Rollback transaction on error
            DB::rollBack();
            
            Log::error("Error combining lessons", [
                'source_lesson_ids' => $sourceLessonIds,
                'target_lesson_id' => $targetLessonId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error combining lessons: ' . $e->getMessage(),
            ], 500);
        }
    }
}

