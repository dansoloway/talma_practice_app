<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GrammarSet extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'source_file',
    ];

    /**
     * Get all grammar concepts in this set.
     */
    public function grammarConcepts(): HasMany
    {
        return $this->hasMany(GrammarConcept::class)->ordered();
    }

    /**
     * Get all lessons that use this grammar set.
     */
    public function lessons(): BelongsToMany
    {
        return $this->belongsToMany(Lesson::class, 'grammar_set_lesson')
            ->withTimestamps();
    }

    /**
     * Get the count of grammar concepts in this set.
     */
    public function getConceptsCountAttribute(): int
    {
        return $this->grammarConcepts()->count();
    }
}
