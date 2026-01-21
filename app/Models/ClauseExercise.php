<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ClauseExercise extends Model
{
    use HasFactory;

    protected $fillable = [
        'lesson_id',
        'grammar_set_id',
        'title',
        'topic',
        'paragraph_text',
        'correct_answers',
        'blank_positions',
        'blank_metadata',
        'difficulty_level',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'correct_answers' => 'array',
        'blank_positions' => 'array',
        'blank_metadata' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * The relationships that should be touched when this model is updated.
     */
    protected $touches = ['lesson'];

    /**
     * Get the lesson this exercise belongs to.
     */
    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    /**
     * Get the grammar set this exercise uses.
     */
    public function grammarSet(): BelongsTo
    {
        return $this->belongsTo(GrammarSet::class);
    }

    /**
     * Get the vocabulary items used in this exercise.
     */
    public function vocabulary(): BelongsToMany
    {
        $vocabularyIds = collect($this->correct_answers)->values()->unique();
        return $this->lesson->vocabulary()->whereIn('id', $vocabularyIds);
    }
}
