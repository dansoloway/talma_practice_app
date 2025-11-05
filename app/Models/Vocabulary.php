<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Vocabulary extends Model
{
    use HasFactory;

    protected $table = 'vocabulary';

    protected $fillable = [
        'lesson_id',
        'english_word',
        'hebrew_translation',
        'arabic_translation',
        'image_path',
        'word_audio_path',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the lesson that owns this vocabulary item.
     */
    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    /**
     * Scope to only active vocabulary items.
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
     * Get the full URL for the word audio file.
     */
    public function getWordAudioUrlAttribute(): ?string
    {
        if (!$this->word_audio_path) {
            return null;
        }

        // If path already starts with /storage/, use it directly with asset()
        // (This matches how prompts store their paths)
        if (strpos($this->word_audio_path, '/storage/') === 0) {
            return asset($this->word_audio_path);
        }

        // Otherwise, assume it's a relative path without /storage/ prefix
        // (for backward compatibility with old paths like vocabulary-audio/vocab_10.mp3)
        return asset('storage/' . $this->word_audio_path);
    }

    /**
     * Check if the audio file actually exists on disk.
     */
    public function hasAudioFile(): bool
    {
        if (!$this->word_audio_path) {
            return false;
        }

        // Extract relative path for Storage check (remove /storage/ prefix if present)
        $relativePath = str_replace('/storage/', '', ltrim($this->word_audio_path, '/'));
        
        return \Illuminate\Support\Facades\Storage::disk('public')->exists($relativePath);
    }
}