<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PromptOptionAsset extends Model
{
    use HasFactory;

    protected $fillable = [
        'prompt_id',
        'option_id',
        'generated_sentence',
        'audio_path',
        'duration_ms',
    ];

    protected $casts = [
        'duration_ms' => 'integer',
    ];

    /**
     * Get the prompt this asset belongs to.
     */
    public function prompt(): BelongsTo
    {
        return $this->belongsTo(Prompt::class);
    }

    /**
     * Get the option this asset belongs to.
     */
    public function option(): BelongsTo
    {
        return $this->belongsTo(Option::class);
    }

    /**
     * Get the full audio URL.
     */
    public function getAudioUrlAttribute(): string
    {
        return asset($this->audio_path);
    }
}

