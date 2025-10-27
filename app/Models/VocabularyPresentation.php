<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VocabularyPresentation extends Model
{
    use HasFactory;

    protected $fillable = [
        'lesson_id',
        'title',
        'description',
        'vocabulary_ids',
        'words_per_row',
        'show_images',
        'show_audio',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'vocabulary_ids' => 'array',
        'show_images' => 'boolean',
        'show_audio' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }
}