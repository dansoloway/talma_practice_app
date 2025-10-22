<?php

namespace App\Http\Controllers;

use App\Models\Prompt;
use App\Models\PromptOptionAsset;
use Illuminate\Http\JsonResponse;

class PromptModelController extends Controller
{
    /**
     * Get the model sentence and audio for a specific prompt + option.
     */
    public function show(int $promptId, int $optionId): JsonResponse
    {
        $asset = PromptOptionAsset::where('prompt_id', $promptId)
            ->where('option_id', $optionId)
            ->firstOrFail();

        return response()->json([
            'generated_sentence' => $asset->generated_sentence,
            'audio_url' => $asset->audio_url,
            'duration_ms' => $asset->duration_ms,
        ]);
    }
}

