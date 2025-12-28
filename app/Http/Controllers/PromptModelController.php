<?php

namespace App\Http\Controllers;

use App\Models\Prompt;
use App\Models\PromptOptionAsset;
use Illuminate\Http\JsonResponse;

class PromptModelController extends Controller
{
    /**
     * Get the model sentence and audio for a specific prompt + option.
     * Only accessible if prompt belongs to an active lesson.
     */
    public function show(int $promptId, int $optionId): JsonResponse
    {
        $asset = PromptOptionAsset::where('prompt_id', $promptId)
            ->where('option_id', $optionId)
            ->whereHas('prompt.lesson', function ($query) {
                $query->where('is_active', true)
                      ->whereNull('archived_at');
            })
            ->firstOrFail();

        return response()->json([
            'generated_sentence' => $asset->generated_sentence,
            'audio_url' => $asset->audio_url,
            'duration_ms' => $asset->duration_ms,
        ]);
    }
}

