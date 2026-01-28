<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Course extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'slug',
        'description',
        'cover_image_path',
        'sort_order',
        'is_active',
        'archived_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'archived_at' => 'datetime',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($course) {
            if (empty($course->slug)) {
                $course->slug = Str::slug($course->title);
            }
        });
    }

    /**
     * Get all lessons for this course, ordered by session_number (which is now course order).
     */
    public function lessons(): HasMany
    {
        return $this->hasMany(Lesson::class)->orderBy('session_number');
    }

    /**
     * Get active lessons for this course.
     */
    public function activeLessons(): HasMany
    {
        return $this->lessons()->where('is_active', true)->whereNull('archived_at');
    }

    /**
     * Scope to only active, non-archived courses.
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

    /**
     * Archive the course.
     */
    public function archive(): bool
    {
        return $this->update(['archived_at' => now()]);
    }

    /**
     * Unarchive the course.
     */
    public function unarchive(): bool
    {
        return $this->update(['archived_at' => null]);
    }

    /**
     * Check if the course is archived.
     */
    public function isArchived(): bool
    {
        return !is_null($this->archived_at);
    }
}
