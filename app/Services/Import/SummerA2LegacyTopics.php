<?php

namespace App\Services\Import;

/**
 * Legacy A2 lesson topic strings from the original XLSX import.
 * Used so CSV re-imports match existing lesson slugs on production (by day number).
 */
class SummerA2LegacyTopics
{
    /** @var array<int, string> */
    private const BY_DAY = [
        1 => 'Day 1: Introductions/All About Me',
        2 => 'Day 2: Community/Our Town',
        3 => 'Day 3: Careers',
        4 => 'Day 4: Hobbies and Interests',
        5 => 'Day 5: Science Day',
        6 => 'Day 6: Travel',
        7 => 'Day 7: Holidays and Festivals',
        8 => 'Day 8: Art Day',
        9 => 'Day 9: Animal Habitat Day',
        10 => 'Day 10: TALMA Olympics',
        11 => 'Day 11: Mystery Party',
        12 => 'Day 12: Field Day',
        13 => 'Day 13: Entertainment',
        14 => 'Day 14: Water Day',
        15 => 'Day 15: End of Summer/Reflection',
    ];

    public static function forDay(int $dayNumber): ?string
    {
        return self::BY_DAY[$dayNumber] ?? null;
    }
}
