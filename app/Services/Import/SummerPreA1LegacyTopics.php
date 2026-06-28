<?php

namespace App\Services\Import;

/**
 * Legacy Pre-A1 lesson topic strings used in existing production slugs (by day number).
 * CSV display topics may differ; these values control slug matching on re-import.
 */
class SummerPreA1LegacyTopics
{
    /** @var array<int, string> */
    private const BY_DAY = [
        1 => 'Introductions Day',
        2 => 'Family Day',
        3 => 'Community Day 1',
        4 => 'Community Day 2',
        5 => 'Dance Day',
        6 => 'Science Day',
        7 => 'Experiments Day!',
        8 => 'Animals Day',
        9 => 'Olympics Day',
        10 => 'Water Day',
        11 => 'Art Day',
        12 => 'Family Day',
        13 => 'Show-and-Tell Day',
        14 => 'Restaurant Day',
        15 => 'Final Day',
    ];

    public static function forDay(int $dayNumber): ?string
    {
        return self::BY_DAY[$dayNumber] ?? null;
    }
}
