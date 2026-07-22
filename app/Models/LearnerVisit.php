<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LearnerVisit extends Model
{
    public const END_REASON_LOGOUT = 'logout';

    public const END_REASON_IDLE = 'idle';

    public const END_REASON_STILL_OPEN = 'still_open';

    public const IDLE_MINUTES = 30;

    protected $fillable = [
        'organization_id',
        'user_id',
        'parent_student_id',
        'started_at',
        'last_seen_at',
        'ended_at',
        'duration_seconds',
        'end_reason',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'last_seen_at' => 'datetime',
        'ended_at' => 'datetime',
        'duration_seconds' => 'integer',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function parentStudent(): BelongsTo
    {
        return $this->belongsTo(ParentStudent::class);
    }

    public function lessons(): HasMany
    {
        return $this->hasMany(LearnerVisitLesson::class);
    }

    public function isOpen(): bool
    {
        return $this->ended_at === null;
    }

    public function effectiveDurationSeconds(): int
    {
        if ($this->duration_seconds !== null) {
            return (int) $this->duration_seconds;
        }

        $end = $this->ended_at ?? $this->last_seen_at ?? $this->started_at;

        return max(0, $this->started_at->diffInSeconds($end));
    }
}
