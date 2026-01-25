<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrueFalseQuestion extends Model
{
    protected $fillable = [
        'lesson_id',
        'grammar_set_id',
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
     * Get the grammar set this question belongs to.
     */
    public function grammarSet(): BelongsTo
    {
        return $this->belongsTo(\App\Models\GrammarSet::class);
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
}
