<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Response extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'parent_student_id',
        'session_id',
        'device_type',
        'ip_address',
        'country',
        'city',
        'region',
        'lesson_id',
        'prompt_id',
        'option_id',
        'generated_sentence',
        'recording_path',
        'completed_at',
    ];

    protected $casts = [
        'completed_at' => 'datetime',
    ];

    /**
     * Get the lesson this response belongs to.
     */
    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    /**
     * Get the prompt this response belongs to.
     */
    public function prompt(): BelongsTo
    {
        return $this->belongsTo(Prompt::class);
    }

    /**
     * Get the option that was selected.
     */
    public function option(): BelongsTo
    {
        return $this->belongsTo(Option::class);
    }

    public function parentStudent(): BelongsTo
    {
        return $this->belongsTo(ParentStudent::class);
    }

    /**
     * Mark this response as completed.
     */
    public function markCompleted(): void
    {
        $this->update(['completed_at' => now()]);
    }
}

