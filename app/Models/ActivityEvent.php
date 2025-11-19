<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityEvent extends Model
{
    protected $fillable = [
        'session_id',
        'device_type',
        'ip_address',
        'country',
        'city',
        'region',
        'lesson_id',
        'activity_type',
        'activity_id',
        'status',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    public function lesson()
    {
        return $this->belongsTo(Lesson::class);
    }
}
