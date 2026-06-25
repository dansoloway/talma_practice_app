<?php

namespace App\Services\Import;

use Illuminate\Support\Str;

class PromptFromFillBlankBuilder
{
    /**
     * Build prompt data from a fill-in-the-blank question row.
     *
     * @param list<string> $lessonVocabulary English words available as distractors
     * @return array{prompt_text: string, template: string, options: list<string>, correct_answer: int}|null
     */
    public function build(string $question, string $answer, array $lessonVocabulary, int $questionIndex = 1): ?array
    {
        $question = trim($question);
        $answer = trim($answer);

        if ($question === '' || $answer === '') {
            return null;
        }

        if (!preg_match('/\{blank\}/i', $question)) {
            return null;
        }

        $template = preg_replace('/\{blank\}/i', '{}', $question);
        $template = trim((string) $template);

        $options = $this->buildOptions($answer, $lessonVocabulary);
        if ($options === []) {
            return null;
        }

        $promptText = Str::limit($question, 80, '…');
        if ($promptText === '') {
            $promptText = "Question {$questionIndex}";
        }

        return [
            'prompt_text' => $promptText,
            'template' => $template,
            'options' => $options,
            'correct_answer' => 1,
        ];
    }

    /**
     * @param list<string> $lessonVocabulary
     * @return list<string>
     */
    private function buildOptions(string $correctAnswer, array $lessonVocabulary): array
    {
        $correctAnswer = trim($correctAnswer);
        $normalizedCorrect = strtolower($correctAnswer);

        $pool = [];
        foreach ($lessonVocabulary as $word) {
            $word = trim($word);
            if ($word === '') {
                continue;
            }
            if (strtolower($word) === $normalizedCorrect) {
                continue;
            }
            $pool[strtolower($word)] = $word;
        }

        $pool = array_values($pool);
        shuffle($pool);

        $targetCount = min(4, 1 + count($pool));
        $distractorCount = max(0, min(3, $targetCount - 1));
        $distractors = array_slice($pool, 0, $distractorCount);

        $options = array_merge([$correctAnswer], $distractors);

        if (count($options) < 2 && count($pool) > 0) {
            $options[] = $pool[0];
        }

        return array_values(array_unique($options));
    }

    public function normalizeQuestionKey(string $question): string
    {
        $normalized = preg_replace('/\{blank\}/i', '{}', trim($question)) ?? trim($question);

        return strtolower(preg_replace('/\s+/', ' ', $normalized) ?? $normalized);
    }
}
