<?php

namespace App\Services\Import;

class VocabularyWordValidator
{
    public const MIN_WORDS_PER_LESSON = 5;

    public const MAX_WORDS_PER_LESSON = 10;

    /** @var list<string> */
    private const ACTIVITY_PATTERNS = [
        '/event of the day$/i',
        '/\bgames$/i',
        '/brainstorm/i',
        '/guess it!/i',
        '/minute-to-win-it/i',
        '/minute to win it/i',
        '/^let\s/u',
        '/^show us what/i',
        '/^memory time$/i',
        '/^sports,? what do we know/i',
        '/^—+/',
    ];

    /**
     * @return array{valid: bool, reason: string|null}
     */
    public function validate(string $word): array
    {
        $word = trim($word);

        if ($word === '') {
            return ['valid' => false, 'reason' => 'empty word'];
        }

        if (preg_match('/[.!?]$/', $word)) {
            return ['valid' => false, 'reason' => 'sentence ending punctuation'];
        }

        if (!$this->isSingleWord($word)) {
            return ['valid' => false, 'reason' => 'multi-word entry (expected single word)'];
        }

        foreach (self::ACTIVITY_PATTERNS as $pattern) {
            if (preg_match($pattern, $word)) {
                return ['valid' => false, 'reason' => 'activity or slide title pattern'];
            }
        }

        return ['valid' => true, 'reason' => null];
    }

    public function isSingleWord(string $word): bool
    {
        $word = trim($word);
        $word = trim($word, ".,;:\"'");

        if ($word === '') {
            return false;
        }

        return !preg_match('/\s/u', $word);
    }

    /**
     * @return array{valid: bool, reason: string|null}
     */
    public function validateLessonWordCount(int $count): array
    {
        if ($count < self::MIN_WORDS_PER_LESSON) {
            return [
                'valid' => false,
                'reason' => 'fewer than ' . self::MIN_WORDS_PER_LESSON . ' words',
            ];
        }

        if ($count > self::MAX_WORDS_PER_LESSON) {
            return [
                'valid' => false,
                'reason' => 'more than ' . self::MAX_WORDS_PER_LESSON . ' words',
            ];
        }

        return ['valid' => true, 'reason' => null];
    }
}
