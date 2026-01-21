<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class GrammarConcept extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'grammar_set_id',
        'section',
        'grammar_topic',
        'grammar_sub_topic',
    ];

    /**
     * Get a formatted display name for the concept.
     */
    public function getDisplayNameAttribute(): string
    {
        if ($this->section) {
            return "Section {$this->section}: {$this->grammar_topic} - {$this->grammar_sub_topic}";
        }
        return "{$this->grammar_topic} - {$this->grammar_sub_topic}";
    }

    /**
     * Scope to order by section and topic.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('section')
            ->orderBy('grammar_topic')
            ->orderBy('grammar_sub_topic');
    }

    /**
     * Get the grammar set this concept belongs to.
     */
    public function grammarSet(): BelongsTo
    {
        return $this->belongsTo(GrammarSet::class);
    }

    /**
     * Get the lessons that use this grammar concept (through grammar set).
     */
    public function lessons(): BelongsToMany
    {
        return $this->belongsToMany(Lesson::class, 'grammar_set_lesson', 'grammar_set_id', 'lesson_id')
            ->wherePivot('grammar_set_id', $this->grammar_set_id)
            ->withTimestamps();
    }
}
