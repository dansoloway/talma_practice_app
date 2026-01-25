<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TrueFalseGame extends Model
{
    protected $fillable = [
        'lesson_id',
        'title',
        'game_version',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * The relationships that should be touched when this model is updated.
     */
    protected $touches = ['lesson'];

    /**
     * Get the lesson this game belongs to.
     */
    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    /**
     * Get the questions for this game.
     */
    public function questions(): HasMany
    {
        return $this->hasMany(TrueFalseQuestion::class)->orderBy('sort_order');
    }

    /**
     * Scope to only active games.
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
}
