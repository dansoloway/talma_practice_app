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
}

