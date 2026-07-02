<?php

namespace App\Services\Import;

/**
 * Legacy A1 lesson topic strings from existing production slugs (by day number).
 * CSV display topics may differ; these values control slug matching on re-import.
 */
class SummerA1LegacyTopics
{
    /** @var array<int, string> */
    private const BY_DAY = [
        1 => 'Introductions Day',
        2 => 'Prepositions / Directions Day',
        3 => 'Emotions Day',
        4 => 'Mystery Day',
        5 => 'Holiday Celebrations Day',
        6 => 'Community Day (Part 1)',
        7 => 'Community Day (Part 2)',
        8 => 'Animals Day',
        9 => 'Sports Day',
        10 => 'Water Day',
        11 => 'Food Day',
        12 => 'Travel Day',
        13 => 'Game Day',
        14 => 'Weather Day',
        15 => 'Final Day / Celebration',
    ];

    public static function forDay(int $dayNumber): ?string
    {
        return self::BY_DAY[$dayNumber] ?? null;
    }
}
