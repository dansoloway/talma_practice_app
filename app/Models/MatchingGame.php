<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MatchingGame extends Model
{
    use HasFactory;

    protected $fillable = [
        'lesson_id',
        'part_id',
        'title',
        'vocabulary_ids',
        'grid_size',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'vocabulary_ids' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * The relationships that should be touched when this model is updated.
     */
    protected $touches = ['lesson'];

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

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }
}
