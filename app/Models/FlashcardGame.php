<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FlashcardGame extends Model
{
    protected $fillable = [
        'lesson_id',
        'part_id',
        'title',
        'game_types',
        'vocabulary_ids',
        'cards_per_game',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'game_types' => 'array',
        'vocabulary_ids' => 'array',
        'is_active' => 'boolean',
    ];

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    public function part(): BelongsTo
    {
        return $this->belongsTo(Part::class);
    }

    public function vocabulary(): HasMany
    {
        return $this->hasMany(Vocabulary::class, 'lesson_id', 'lesson_id')
            ->whereIn('id', $this->vocabulary_ids ?? []);
    }

    // Available game types
    public static function getGameTypes(): array
    {
        return [
            'image_to_word' => 'Image → Word (Say the word)',
            'image_to_audio' => 'Image → Audio (Choose correct audio)',
            'audio_to_image' => 'Audio → Image (Choose correct image)',
            'audio_to_word' => 'Audio → Word (Choose correct word)',
        ];
    }
}
