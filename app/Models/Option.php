<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Option extends Model
{
    use HasFactory;

    protected $fillable = [
        'prompt_id',
        'label',
        'image_path',
        'word_audio_path',
        'sentence_audio_path',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the prompt this option belongs to.
     */
    public function prompt(): BelongsTo
    {
        return $this->belongsTo(Prompt::class);
    }

    /**
     * Get all assets that include this option.
     */
    public function assets(): HasMany
    {
        return $this->hasMany(PromptOptionAsset::class);
    }

    /**
     * Get all responses that selected this option.
     */
    public function responses(): HasMany
    {
        return $this->hasMany(Response::class);
    }

    /**
     * Scope to only active options.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}

