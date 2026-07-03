<?php

namespace App\Services\Tts;

use App\Http\Controllers\Admin\GeneratesTtsAudio;
use App\Models\Option;
use Illuminate\Support\Facades\Log;

class PromptOptionTtsService
{
    use GeneratesTtsAudio;

    public function __construct(
        private ElevenLabsTtsService $ttsService,
    ) {}

    public function enabled(): bool
    {
        return $this->ttsService->enabled();
    }

    /**
     * Generate word and sentence TTS for a prompt option.
     */
    public function generateForOption(Option $option): bool
    {
        if (!$this->enabled()) {
            return false;
        }

        $wordOk = $this->generateWordForOption($option);

        return $this->generateSentenceForOption($option) && $wordOk;
    }

    public function generateWordForOption(Option $option): bool
    {
        if (!$this->enabled()) {
            return false;
        }

        try {
            $this->generateSingleWordTts($option);

            return true;
        } catch (\Throwable $e) {
            Log::warning('Prompt option word TTS generation failed', [
                'option_id' => $option->id,
                'label' => $option->label,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function generateSentenceForOption(Option $option): bool
    {
        if (!$this->enabled()) {
            return false;
        }

        try {
            $this->generateSingleSentenceTts($option);

            return true;
        } catch (\Throwable $e) {
            Log::warning('Prompt option sentence TTS generation failed', [
                'option_id' => $option->id,
                'label' => $option->label,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * @param iterable<Option> $options
     * @return int Number of options successfully processed
     */
    public function generateForOptions(iterable $options): int
    {
        if (!$this->enabled()) {
            return 0;
        }

        $generated = 0;
        foreach ($options as $option) {
            if (!$option instanceof Option) {
                continue;
            }
            if ($this->generateForOption($option)) {
                $generated++;
            }
        }

        return $generated;
    }
}
