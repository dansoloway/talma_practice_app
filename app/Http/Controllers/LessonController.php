<?php

namespace App\Http\Controllers;

use App\Models\Lesson;
use Illuminate\Http\Request;

class LessonController extends Controller
{
    /**
     * Display a listing of active lessons.
     */
    public function index()
    {
        $lessons = Lesson::active()->ordered()->get();
        
        return view('lessons.index', compact('lessons'));
    }

    /**
     * Display the specified lesson with its activities.
     */
    public function show(string $slug)
    {
        // Build eager loading array
        $withRelations = [
            'vocabulary' => function ($query) {
                $query->where('is_active', true)->orderBy('sort_order');
            },
            'prompts' => function ($query) {
                $query->where('is_active', true)
                      ->orderBy('sort_order')
                      ->with(['options' => function ($opt) {
                          $opt->where('is_active', true)->orderBy('sort_order');
                      }]);
            },
            'matchingGames' => function ($query) {
                $query->where('is_active', true)->orderBy('sort_order');
            },
            'flashcardGames' => function ($query) {
                $query->where('is_active', true)->orderBy('sort_order');
            },
            'spellingGames' => function ($query) {
                $query->where('is_active', true)->orderBy('sort_order');
            },
            'clauseExercises' => function ($query) {
                $query->where('is_active', true)->orderBy('sort_order');
            },
            'sentenceBuilderGames' => function ($query) {
                $query->where('is_active', true)
                      ->orderBy('sort_order')
                      ->with(['questions' => function ($q) {
                          $q->where('is_active', true)->orderBy('sort_order');
                      }]);
            },
            'trueFalseQuestions' => function ($query) {
                $query->where('is_approved', true)
                      ->where('is_active', true)
                      ->orderBy('sort_order');
            }
        ];
        
        // Only eager load trueFalseGames if table exists (after migrations)
        try {
            // Check if table exists by attempting a simple query
            \DB::table('true_false_games')->limit(1)->get();
            $withRelations['trueFalseGames'] = function ($query) {
                $query->where('is_active', true)->orderBy('sort_order');
            };
        } catch (\Exception $e) {
            // Table doesn't exist yet - migrations not run
            // Skip eager loading trueFalseGames
        }
        
        $lesson = Lesson::active()
            ->where('slug', $slug)
            ->where('is_active', true)
            ->with($withRelations)
            ->firstOrFail();
        
        // Add full URLs for audio paths in prompts
        $lesson->prompts->each(function ($prompt) {
            if ($prompt->prompt_audio_path) {
                $prompt->prompt_audio_path = asset($prompt->prompt_audio_path);
            }
            $prompt->options->each(function ($option) {
                if ($option->word_audio_path) {
                    $option->word_audio_path = asset($option->word_audio_path);
                }
                if ($option->image_path) {
                    $option->image_path = asset($option->image_path);
                }
            });
        });
        
        // For review lessons, load vocabulary from source lessons
        if ($lesson->is_review) {
            $lesson->load('reviewSources');
            $lesson->setRelation('vocabulary', $lesson->getVocabularyForGames());
        }

        return view('lessons.show', compact('lesson'));
    }
}

