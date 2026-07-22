<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LearnerVisitLesson extends Model
{
    protected $fillable = [
        'learner_visit_id',
        'lesson_id',
        'first_seen_at',
        'last_seen_at',
    ];

    protected $casts = [
        'first_seen_at' => 'datetime',
        'last_seen_at' => 'datetime',
    ];

    public function visit(): BelongsTo
    {
        return $this->belongsTo(LearnerVisit::class, 'learner_visit_id');
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }
}
