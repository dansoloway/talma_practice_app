<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lesson extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'slug',
        'instructions',
        'grade_level',
        'session_number',
        'session_title',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get all parts for this lesson, ordered by sort_order.
     */
    public function parts(): HasMany
    {
        return $this->hasMany(Part::class)->orderBy('sort_order');
    }

    /**
     * Get all prompts for this lesson (through parts), ordered by sort_order.
     */
    public function prompts(): HasMany
    {
        return $this->hasMany(Prompt::class)->orderBy('sort_order');
    }

    /**
     * Get all vocabulary items for this lesson.
     */
    public function vocabulary(): HasMany
    {
        return $this->hasMany(Vocabulary::class)->orderBy('sort_order');
    }

    public function matchingGames(): HasMany
    {
        return $this->hasMany(MatchingGame::class)->orderBy('sort_order');
    }

    /**
     * Get all responses for this lesson.
     */
    public function responses(): HasMany
    {
        return $this->hasMany(Response::class);
    }

    /**
     * Scope to only active lessons.
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
}

