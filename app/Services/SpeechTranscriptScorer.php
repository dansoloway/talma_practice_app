<?php

namespace App\Services;

/**
 * Lenient transcript-vs-target scoring for ESL speaking feedback.
 * Mirrors public/js/talma-speech.js so PHPUnit can cover the same rules.
 */
class SpeechTranscriptScorer
{
    public const DEFAULT_PASS_RATIO = 0.75;

    public const SINGLE_WORD_LEVENSHTEIN_THRESHOLD = 0.75;

    public const WORD_LEVENSHTEIN_THRESHOLD = 0.8;

    /**
     * @return array{pass: bool, ratio: float, heard: string, normalizedTarget: string, normalizedTranscript: string}
     */
    public static function score(string $transcript, string $target, float $passRatio = self::DEFAULT_PASS_RATIO): array
    {
        $targetWords = self::tokenize($target);
        $spokenWords = self::tokenize($transcript);

        if ($targetWords === []) {
            return self::result(false, 0.0, $transcript, $target, $transcript);
        }

        if ($spokenWords === []) {
            return self::result(false, 0.0, $transcript, $target, $transcript);
        }

        if (count($targetWords) === 1) {
            $targetWord = $targetWords[0];
            foreach ($spokenWords as $spoken) {
                if ($spoken === $targetWord || self::levenshteinRatio($spoken, $targetWord) >= self::SINGLE_WORD_LEVENSHTEIN_THRESHOLD) {
                    return self::result(true, 1.0, $transcript, $target, $transcript);
                }
            }

            return self::result(false, 0.0, $transcript, $target, $transcript);
        }

        $remaining = $spokenWords;
        $matched = 0;

        foreach ($targetWords as $targetWord) {
            $index = self::findMatchingWordIndex($remaining, $targetWord);
            if ($index !== null) {
                $matched++;
                array_splice($remaining, $index, 1);
            }
        }

        $ratio = $matched / count($targetWords);

        return self::result($ratio >= $passRatio, $ratio, $transcript, $target, $transcript);
    }

    public static function normalize(string $text): string
    {
        $lower = mb_strtolower($text, 'UTF-8');
        $stripped = preg_replace("/[^\p{L}\p{N}\s']/u", ' ', $lower) ?? $lower;
        $collapsed = preg_replace('/\s+/u', ' ', trim($stripped)) ?? trim($stripped);

        return $collapsed;
    }

    /**
     * @return list<string>
     */
    public static function tokenize(string $text): array
    {
        $normalized = self::normalize($text);
        if ($normalized === '') {
            return [];
        }

        return array_values(array_filter(explode(' ', $normalized), fn ($w) => $w !== ''));
    }

    /**
     * @param list<string> $words
     */
    private static function findMatchingWordIndex(array $words, string $targetWord): ?int
    {
        foreach ($words as $index => $word) {
            if ($word === $targetWord || self::levenshteinRatio($word, $targetWord) >= self::WORD_LEVENSHTEIN_THRESHOLD) {
                return $index;
            }
        }

        return null;
    }

    private static function levenshteinRatio(string $a, string $b): float
    {
        if ($a === $b) {
            return 1.0;
        }

        $maxLen = max(strlen($a), strlen($b));
        if ($maxLen === 0) {
            return 1.0;
        }

        return 1.0 - (levenshtein($a, $b) / $maxLen);
    }

    /**
     * @return array{pass: bool, ratio: float, heard: string, normalizedTarget: string, normalizedTranscript: string}
     */
    private static function result(bool $pass, float $ratio, string $heard, string $target, string $transcript): array
    {
        return [
            'pass' => $pass,
            'ratio' => round($ratio, 4),
            'heard' => $heard,
            'normalizedTarget' => self::normalize($target),
            'normalizedTranscript' => self::normalize($transcript),
        ];
    }
}
