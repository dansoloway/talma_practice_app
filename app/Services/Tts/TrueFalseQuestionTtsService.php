<?php

namespace App\Services\Tts;

use App\Models\TrueFalseQuestion;
use Illuminate\Support\Facades\Log;

class TrueFalseQuestionTtsService
{
    public function __construct(
        private ElevenLabsTtsService $tts
    ) {}

    public function enabled(): bool
    {
        return $this->tts->enabled();
    }

    /**
     * Generate TTS audio for a question statement and persist audio_path.
     */
    public function generate(TrueFalseQuestion $question): bool
    {
        if (!$this->enabled()) {
            return false;
        }

        $statement = trim($question->statement ?? '');
        if ($statement === '') {
            return false;
        }

        $relativePath = sprintf(
            'tts/true-false/lesson_%d_question_%d.mp3',
            $question->lesson_id,
            $question->id
        );

        try {
            $result = $this->tts->generateAndSaveSentence(
                $statement,
                $relativePath,
                $question->audio_path
            );

            if ($result === null) {
                return false;
            }

            $question->updateQuietly(['audio_path' => $result['path']]);

            return true;
        } catch (\Exception $e) {
            Log::warning('True/False TTS generation failed', [
                'question_id' => $question->id,
                'lesson_id' => $question->lesson_id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Generate audio only when missing.
     */
    public function ensure(TrueFalseQuestion $question): bool
    {
        if (!empty($question->audio_path)) {
            return true;
        }

        return $this->generate($question);
    }

    /**
     * Generate audio for all questions in a game that are missing it.
     *
     * @return int Number of questions successfully generated
     */
    public function ensureForGame(iterable $questions): int
    {
        $generated = 0;

        foreach ($questions as $question) {
            if (!$question instanceof TrueFalseQuestion) {
                continue;
            }

            if (!empty($question->audio_path)) {
                continue;
            }

            if ($this->generate($question)) {
                $generated++;
            }
        }

        return $generated;
    }
}
