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
        $lessons = Lesson::with([
            'vocabulary',
            'matchingGames',
            'flashcardGames',
            'spellingGames',
            'sentenceBuilderGames',
            'trueFalseQuestions',
            'prompts',
        ])->orderBy('grade_level')
          ->orderBy('session_number')
          ->orderBy('sort_order')
          ->get();

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

        return view('admin.lesson-tracker', compact('lessons'));
    }

    /**
     * Update lesson assignment and status.
     */
    public function update(Request $request, Lesson $lesson)
    {
        $validated = $request->validate([
            'assigned_to' => 'nullable|string|in:Unassigned,Leila,Jen',
            'status' => 'required|string|in:not_started,in_progress,done,stuck',
        ]);

        // Convert "Unassigned" to null
        if ($validated['assigned_to'] === 'Unassigned') {
            $validated['assigned_to'] = null;
        }

        $lesson->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Lesson updated successfully',
        ]);
    }
}

