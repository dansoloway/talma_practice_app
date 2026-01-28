<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lesson extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'slug',
        'instructions',
        'cover_image_path',
        'course_id',
        'grade_level',
        'session_number',
        'part_number',
        'session_title',
        'is_active',
        'is_review',
        'sort_order',
        'archived_at',
        'assigned_to',
        'status',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_review' => 'boolean',
        'archived_at' => 'datetime',
    ];

    /**
     * Get the course that owns this lesson.
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

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

    public function flashcardGames(): HasMany
    {
        return $this->hasMany(FlashcardGame::class)->orderBy('sort_order');
    }

    public function vocabularyPresentations(): HasMany
    {
        return $this->hasMany(VocabularyPresentation::class)->orderBy('sort_order');
    }

    public function trueFalseQuestions(): HasMany
    {
        return $this->hasMany(TrueFalseQuestion::class)->orderBy('sort_order');
    }

    public function trueFalseGames(): HasMany
    {
        return $this->hasMany(TrueFalseGame::class)->orderBy('sort_order');
    }

    public function spellingGames(): HasMany
    {
        return $this->hasMany(SpellingGame::class)->orderBy('sort_order');
    }

    public function sentenceBuilderGames(): HasMany
    {
        return $this->hasMany(SentenceBuilderGame::class)->orderBy('sort_order');
    }

    /**
     * Get all grammar sets associated with this lesson.
     */
    public function grammarSets(): BelongsToMany
    {
        return $this->belongsToMany(\App\Models\GrammarSet::class, 'grammar_set_lesson')
            ->withTimestamps();
    }

    /**
     * Get all grammar concepts associated with this lesson (through grammar sets).
     */
    public function grammarConcepts(): BelongsToMany
    {
        return $this->belongsToMany(\App\Models\GrammarConcept::class, 'grammar_set_lesson', 'lesson_id', 'grammar_set_id')
            ->withTimestamps();
    }

    /**
     * Get all clause exercises for this lesson.
     */
    public function clauseExercises(): HasMany
    {
        return $this->hasMany(ClauseExercise::class)->orderBy('sort_order');
    }

    /**
     * Get all source lessons that this review lesson reviews.
     */
    public function reviewSources(): BelongsToMany
    {
        return $this->belongsToMany(Lesson::class, 'lesson_review_sources', 'review_lesson_id', 'source_lesson_id')
            ->withPivot('order')
            ->withTimestamps()
            ->orderBy('lesson_review_sources.order');
    }

    /**
     * Get all review lessons that review this lesson.
     */
    public function reviewLessons(): BelongsToMany
    {
        return $this->belongsToMany(Lesson::class, 'lesson_review_sources', 'source_lesson_id', 'review_lesson_id')
            ->withPivot('order')
            ->withTimestamps()
            ->orderBy('lesson_review_sources.order');
    }

    /**
     * Get vocabulary for this lesson, or aggregated vocabulary from source lessons if this is a review lesson.
     * Returns a Collection of Vocabulary models, filtered for active items and ordered.
     */
    public function getVocabularyForGames()
    {
        if ($this->is_review && $this->reviewSources->count() > 0) {
            // Get vocabulary from all source lessons
            $sourceLessonIds = $this->reviewSources->pluck('id');
            return Vocabulary::whereIn('lesson_id', $sourceLessonIds)
                ->where('is_active', true)
                ->orderBy('lesson_id')
                ->orderBy('sort_order')
                ->get();
        }
        
        // Regular lesson - return its own vocabulary
        return $this->vocabulary()->where('is_active', true)->orderBy('sort_order')->get();
    }

    /**
     * Get all responses for this lesson.
     */
    public function responses(): HasMany
    {
        return $this->hasMany(Response::class);
    }

    /**
     * Scope to only active, non-archived lessons.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)->whereNull('archived_at');
    }

    /**
     * Scope to order by sort_order.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }

    /**
     * Get or create a default part for this lesson.
     * This ensures all content has a part to belong to.
     */
    public function getOrCreateDefaultPart()
    {
        // Check if lesson has any parts
        $existingPart = $this->parts()->first();
        
        if ($existingPart) {
            return $existingPart;
        }

        // Create a default part
        return $this->parts()->create([
            'title' => 'Main Activities',
            'description' => 'Practice activities for this lesson',
            'is_active' => true,
            'sort_order' => 1,
        ]);
    }

    /**
     * Get the last part for this lesson (for adding new content).
     */
    public function getLastPart()
    {
        $lastPart = $this->parts()->orderBy('sort_order', 'desc')->first();
        
        if (!$lastPart) {
            return $this->getOrCreateDefaultPart();
        }
        
        return $lastPart;
    }


    /**
     * Scope to get only archived lessons.
     */
    public function scopeArchived($query)
    {
        return $query->whereNotNull('archived_at');
    }

    /**
     * Check if the lesson is archived.
     */
    public function isArchived(): bool
    {
        return !is_null($this->archived_at);
    }

    /**
     * Archive the lesson.
     */
    public function archive(): bool
    {
        return $this->update(['archived_at' => now()]);
    }

    /**
     * Unarchive the lesson.
     */
    public function unarchive(): bool
    {
        return $this->update(['archived_at' => null]);
    }

    /**
     * Get standardized display name with session information.
     * Format: "Grade X - Session Y: [Title]" or "Session Y: [Title]" or just "[Title]"
     */
    public function getDisplayNameAttribute(): string
    {
        $parts = [];
        
        if ($this->grade_level) {
            $parts[] = "Grade {$this->grade_level}";
        }
        
        if ($this->session_number) {
            $parts[] = "Session {$this->session_number}";
        }
        
        $prefix = !empty($parts) ? implode(' - ', $parts) . ': ' : '';
        
        return $prefix . $this->title;
    }

    /**
     * Get short display name (without grade/session prefix).
     */
    public function getShortDisplayNameAttribute(): string
    {
        return $this->title;
    }

    /**
     * Generate standardized activity name.
     * Format: "[Display Name] - [Activity Type] [Number]"
     */
    public function generateActivityName(string $activityType, int $number = null): string
    {
        $activityTypes = [
            'matching' => 'Matching Game',
            'flashcard' => 'Flashcard Game',
            'spelling' => 'Spelling Practice',
            'sentence_builder' => 'Sentence Builder',
            'true_false' => 'True/False Questions',
        ];
        
        $typeLabel = $activityTypes[$activityType] ?? ucfirst($activityType);
        
        // Use short display name (just title) for activities to avoid redundancy
        $baseName = $this->short_display_name;
        
        if ($number !== null) {
            return "{$baseName} - {$typeLabel} {$number}";
        }
        
        return "{$baseName} - {$typeLabel}";
    }

    /**
     * Get the full URL for the cover image.
     */
    public function getCoverImageUrlAttribute(): ?string
    {
        if (!$this->cover_image_path) {
            return null;
        }

        // If path already starts with /storage/, use it directly
        if (strpos($this->cover_image_path, '/storage/') === 0) {
            return asset($this->cover_image_path);
        }

        // Otherwise, prepend storage/ to the relative path
        return asset('storage/' . $this->cover_image_path);
    }
}

