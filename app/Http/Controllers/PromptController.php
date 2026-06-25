<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\GuardsRestrictedCourseAccess;
use App\Models\Prompt;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class PromptController extends Controller
{
    use GuardsRestrictedCourseAccess;
    /**
     * Get prompt details with options (no audio yet).
     * Only accessible if prompt belongs to an active lesson.
     */
    public function show(int $id): JsonResponse
    {
        $prompt = Prompt::with(['options' => function ($query) {
            $query->where('is_active', true);
        }])
        ->whereHas('lesson', function ($query) {
            $query->where('is_active', true)
                  ->whereNull('archived_at');
        })
        ->findOrFail($id);

        return response()->json([
            'id' => $prompt->id,
            'prompt_text' => $prompt->prompt_text,
            'options' => $prompt->options->map(function ($option) {
                return [
                    'id' => $option->id,
                    'label' => $option->label,
                    'option_type' => $option->option_type,
                    'option_text' => $option->option_text,
                    'image_path' => $option->image_path ? asset($option->image_path) : null,
                    'word_audio_path' => $option->word_audio_path ? asset($option->word_audio_path) : null,
                ];
            }),
        ]);
    }

    /**
     * Play all prompts for a lesson (sentence completion activity).
     * Only accessible if lesson is active and not archived.
     */
    public function play($lessonId)
    {
        $lesson = \App\Models\Lesson::where('is_active', true)
            ->whereNull('archived_at')
            ->with([
                'prompts' => function ($query) {
                    $query->where('is_active', true)
                          ->orderBy('sort_order')
                          ->with(['options' => function ($opt) {
                              $opt->where('is_active', true)->orderBy('sort_order');
                          }]);
                }
            ])
            ->findOrFail($lessonId);

        $gate = $this->ensureLegacyCourseAccess($lesson);
        if ($gate instanceof RedirectResponse) {
            return $gate;
        }

        // Add full URLs for audio paths
        $lesson->prompts->each(function ($prompt) {
            if ($prompt->prompt_audio_path) {
                $prompt->prompt_audio_path = asset($prompt->prompt_audio_path);
            }
            $prompt->options->each(function ($option) {
                if ($option->word_audio_path) {
                    $option->word_audio_path = asset($option->word_audio_path);
                }
                if ($option->sentence_audio_path) {
                    $option->sentence_audio_path = asset($option->sentence_audio_path);
                }
            });
        });

        return view('prompts.play', compact('lesson'));
    }
}

