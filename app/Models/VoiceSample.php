<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VoiceSample extends Model
{
    public const GENDER_MALE = 'male';

    public const GENDER_FEMALE = 'female';

    protected $fillable = [
        'organization_id',
        'lesson_id',
        'vocabulary_id',
        'prompt_id',
        'option_id',
        'target_text',
        'age',
        'gender',
        'native_language',
        's3_key',
        'metadata_s3_key',
        'duration_ms',
        'mime_original',
        'recorded_at',
    ];

    protected function casts(): array
    {
        return [
            'recorded_at' => 'datetime',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    public function prompt(): BelongsTo
    {
        return $this->belongsTo(Prompt::class);
    }

    public function option(): BelongsTo
    {
        return $this->belongsTo(Option::class);
    }

    public function vocabulary(): BelongsTo
    {
        return $this->belongsTo(Vocabulary::class);
    }
}
