<?php

namespace App\Services\Tts;

use App\Models\Option;
use App\Models\PromptOptionAsset;

class OptionSentenceAudioResolver
{
    /**
     * Resolve the relative storage path for an option's sentence audio.
     */
    public function resolveRelativePath(Option $option): ?string
    {
        $sentencePath = $option->getAttributes()['sentence_audio_path'] ?? null;
        if ($sentencePath && $this->fileExists($sentencePath)) {
            return $sentencePath;
        }

        $asset = PromptOptionAsset::query()
            ->where('prompt_id', $option->prompt_id)
            ->where('option_id', $option->id)
            ->first();

        if ($asset?->audio_path && $this->fileExists($asset->audio_path)) {
            return $asset->audio_path;
        }

        return null;
    }

    /**
     * Resolve a public URL for an option's sentence audio.
     */
    public function resolveUrl(Option $option): ?string
    {
        $path = $this->resolveRelativePath($option);

        return $path ? asset($path) : null;
    }

    private function fileExists(string $path): bool
    {
        return file_exists(public_path(ltrim($path, '/')));
    }
}
