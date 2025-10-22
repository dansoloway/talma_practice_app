<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Prompt extends Model
{
    use HasFactory;

    protected $fillable = [
        'lesson_id',
        'part_id',
        'prompt_text',
        'template',
        'tts_voice',
        'prompt_audio_path',
        'sort_order',
    ];

    /**
     * Get the lesson this prompt belongs to.
     */
    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    /**
     * Get the part this prompt belongs to.
     */
    public function part(): BelongsTo
    {
        return $this->belongsTo(Part::class);
    }

    /**
     * Get all options for this prompt, ordered by sort_order.
     */
    public function options(): HasMany
    {
        return $this->hasMany(Option::class)->orderBy('sort_order');
    }

    /**
     * Get all pre-generated assets for this prompt.
     */
    public function assets(): HasMany
    {
        return $this->hasMany(PromptOptionAsset::class);
    }

    /**
     * Get all responses for this prompt.
     */
    public function responses(): HasMany
    {
        return $this->hasMany(Response::class);
    }

    /**
     * Get the asset for a specific option.
     */
    public function getAssetForOption(int $optionId): ?PromptOptionAsset
    {
        return $this->assets()->where('option_id', $optionId)->first();
    }
}

