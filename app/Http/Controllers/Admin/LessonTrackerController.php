<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Lesson;
use Illuminate\Http\Request;

class LessonTrackerController extends Controller
{
    /**
     * Display the lesson tracker.
     */
    public function index(Request $request)
    {
        // Get filter parameters
        $assignedTo = $request->input('assigned_to');
        $gradeLevel = $request->input('grade_level');
        $status = $request->input('status');
        $sessionNumber = $request->input('session_number');
        $search = $request->input('search');
        $showArchived = $request->boolean('view_archived');
        
        // Build query with filters
        if ($showArchived) {
            $query = Lesson::whereNotNull('archived_at');
        } else {
            $query = Lesson::whereNull('archived_at')
                ->where('is_active', true);
        }
        
        $query->with([
            'vocabulary',
            'matchingGames',
            'flashcardGames',
            'spellingGames',
            'sentenceBuilderGames',
            'trueFalseQuestions',
            'prompts',
        ]);
        
        // Apply filters
        if ($assignedTo !== null && $assignedTo !== '') {
            if ($assignedTo === 'Unassigned') {
                $query->whereNull('assigned_to');
            } else {
                $query->where('assigned_to', $assignedTo);
            }
        }
        
        if ($gradeLevel) {
            $query->where('grade_level', $gradeLevel);
        }
        
        if ($status) {
            $query->where('status', $status);
        }
        
        if ($sessionNumber) {
            $query->where('session_number', $sessionNumber);
        }
        
        // Filter by search text (title, session_title, slug)
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'LIKE', "%{$search}%")
                  ->orWhere('session_title', 'LIKE', "%{$search}%")
                  ->orWhere('slug', 'LIKE', "%{$search}%");
            });
        }
        
        $lessons = $query->orderBy('grade_level')
          ->orderBy('session_number')
          ->orderBy('sort_order')
          ->get();
        
        // Get unique grade levels for filter dropdown
        $gradeLevels = Lesson::whereNotNull('grade_level')
            ->distinct('grade_level')
            ->orderBy('grade_level')
            ->pluck('grade_level');
        
        // Get unique session numbers for filter dropdown
        $sessionNumbers = Lesson::whereNotNull('session_number')
            ->distinct('session_number')
            ->orderBy('session_number')
            ->pluck('session_number');

        // Process lessons to check component status
        $lessons = $lessons->map(function ($lesson) {
            $vocabulary = $lesson->vocabulary->where('is_active', true);
            $vocabCount = $vocabulary->count();
            
            // Check vocabulary components
            $vocabWithImages = $vocabulary->filter(function ($vocab) {
                return !empty($vocab->image_path);
            })->count();
            
            $vocabWithTts = $vocabulary->filter(function ($vocab) {
                return !empty($vocab->word_audio_path);
            })->count();
            
            // Count activities
            $matchingGamesCount = $lesson->matchingGames->count();
            $flashcardGamesCount = $lesson->flashcardGames->count();
            $spellingGamesCount = $lesson->spellingGames->count();
            $sentenceBuilderGamesCount = $lesson->sentenceBuilderGames->count();
            $trueFalseQuestionsCount = $lesson->trueFalseQuestions->count();
            $promptsCount = $lesson->prompts->count();
            
            return [
                'lesson' => $lesson,
                'components' => [
                    'vocabulary' => [
                        'has' => $vocabCount > 0,
                        'count' => $vocabCount,
                        'with_images' => $vocabWithImages,
                        'with_tts' => $vocabWithTts,
                    ],
                    'matching_games' => [
                        'has' => $matchingGamesCount > 0,
                        'count' => $matchingGamesCount,
                    ],
                    'flashcard_games' => [
                        'has' => $flashcardGamesCount > 0,
                        'count' => $flashcardGamesCount,
                    ],
                    'spelling_games' => [
                        'has' => $spellingGamesCount > 0,
                        'count' => $spellingGamesCount,
                    ],
                    'sentence_builder_games' => [
                        'has' => $sentenceBuilderGamesCount > 0,
                        'count' => $sentenceBuilderGamesCount,
                    ],
                    'true_false_questions' => [
                        'has' => $trueFalseQuestionsCount > 0,
                        'count' => $trueFalseQuestionsCount,
                    ],
                    'prompts' => [
                        'has' => $promptsCount > 0,
                        'count' => $promptsCount,
                    ],
                ],
            ];
        });

        return view('admin.lesson-tracker', compact('lessons', 'gradeLevels', 'sessionNumbers', 'assignedTo', 'gradeLevel', 'status', 'sessionNumber', 'search', 'showArchived'));
    }

    /**
     * Update lesson assignment and status.
     */
    public function update(Request $request, Lesson $lesson)
    {
        $validated = $request->validate([
            'assigned_to' => 'nullable|string|in:Unassigned,Leila,Jen,Daniel',
            'status' => 'nullable|string|in:not_started,in_progress,done,stuck',
            'admin_notes' => 'nullable|string',
        ]);

        $updateData = [];

        // Handle assigned_to if provided
        if (isset($validated['assigned_to'])) {
            // Convert "Unassigned" to null
            $updateData['assigned_to'] = $validated['assigned_to'] === 'Unassigned' ? null : $validated['assigned_to'];
        }

        // Handle status if provided
        if (isset($validated['status'])) {
            $updateData['status'] = $validated['status'];
        }
        
        // Handle admin_notes if provided
        if (isset($validated['admin_notes'])) {
            $updateData['admin_notes'] = $validated['admin_notes'];
        }

        // Only update if there's data to update
        if (!empty($updateData)) {
            $lesson->update($updateData);
        }

        return response()->json([
            'success' => true,
            'message' => 'Lesson updated successfully',
        ]);
    }
}

