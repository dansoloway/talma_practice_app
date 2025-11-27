<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SentenceBuilderQuestion extends Model
{
    use HasFactory;

    protected $fillable = [
        'sentence_builder_game_id',
        'correct_sentence',
        'word_options',
        'explanation',
        'difficulty',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'correct_sentence' => 'array',
        'word_options' => 'array',
        'is_active' => 'boolean',
    ];

    public function game(): BelongsTo
    {
        return $this->belongsTo(SentenceBuilderGame::class, 'sentence_builder_game_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }

    public static function getDifficulties(): array
    {
        return [
            'easy' => 'Easy (3 words)',
            'medium' => 'Medium (4 words)',
            'hard' => 'Hard (5 words)',
        ];
    }
}
