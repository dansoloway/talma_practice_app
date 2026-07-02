<?php

namespace App\Services\Import;

/**
 * Official B1 lesson topic strings (by day number) for slug matching on re-import.
 */
class SummerB1LegacyTopics
{
    /** @var array<int, string> */
    private const BY_DAY = [
        1 => 'Introductions & Self-Portraits / Hobbies',
        2 => 'Community Connection',
        3 => 'In the Future',
        4 => 'Keeping Traditions',
        5 => 'Goal Setting / Variety Show',
        6 => 'AI',
        7 => 'Water Conservation',
        8 => 'Inventions',
        9 => 'Mysteries of the Brain',
        10 => 'Experiments Day',
        11 => 'Relaxation / Stress Relief',
        12 => 'Impact of Music',
        13 => 'Behind the Scenes',
        14 => 'Sports/ Olympics',
        15 => 'End of Summer',
    ];

    public static function forDay(int $dayNumber): ?string
    {
        return self::BY_DAY[$dayNumber] ?? null;
    }
}
