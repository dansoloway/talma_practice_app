<?php

namespace App\Http\Controllers;

use App\Models\Prompt;
use Illuminate\Http\JsonResponse;

class PromptController extends Controller
{
    /**
     * Get prompt details with options (no audio yet).
     */
    public function show(int $id): JsonResponse
    {
        $prompt = Prompt::with(['options' => function ($query) {
            $query->where('is_active', true);
        }])->findOrFail($id);

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
     */
    public function play($lessonId)
    {
        $lesson = \App\Models\Lesson::with([
            'prompts' => function ($query) {
                $query->where('is_active', true)
                      ->orderBy('sort_order')
                      ->with(['options' => function ($opt) {
                          $opt->where('is_active', true)->orderBy('sort_order');
                      }]);
            }
        ])->findOrFail($lessonId);

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

