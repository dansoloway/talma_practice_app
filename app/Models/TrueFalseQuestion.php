<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class TrueFalseQuestion extends Model
{
    protected $fillable = [
        'lesson_id',
        'true_false_game_id',
        'game_version',
        'statement',
        'is_true',
        'explanation',
        'category',
        'audio_path',
        'is_approved',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_true' => 'boolean',
        'is_approved' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * The relationships that should be touched when this model is updated.
     */
    protected $touches = ['lesson'];

    /**
     * Get the lesson this question belongs to.
     */
    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    /**
     * Get the game this question belongs to.
     */
    public function game(): BelongsTo
    {
        return $this->belongsTo(TrueFalseGame::class, 'true_false_game_id');
    }

    /**
     * Get the vocabulary items this question tests.
     */
    public function vocabulary(): BelongsToMany
    {
        return $this->belongsToMany(Vocabulary::class, 'true_false_question_vocabulary')
            ->withTimestamps();
    }

    /**
     * Scope to only approved questions.
     */
    public function scopeApproved($query)
    {
        return $query->where('is_approved', true);
    }

    /**
     * Scope to only active questions.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to order by sort_order.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }

    /**
     * Scope to filter by game version.
     */
    public function scopeForVersion($query, string $version)
    {
        return $query->where('game_version', $version);
    }

    /**
     * Get available game versions.
     */
    public static function getGameVersions(): array
    {
        return ['easy', 'medium', 'hard'];
    }
}
