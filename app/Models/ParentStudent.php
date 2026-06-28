<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ParentStudent extends Model
{
    use HasFactory;

    public const GENDERS = [
        'male' => ['he' => 'Male', 'en' => 'Male'],
        'female' => ['he' => 'Female', 'en' => 'Female'],
        'other' => ['he' => 'Other', 'en' => 'Other'],
    ];

    protected $fillable = [
        'parent_id',
        'user_id',
        'first_name',
        'last_name',
        'first_name_english',
        'last_name_english',
        'birth_date',
        'grade',
        'gender',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'grade' => 'integer',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'parent_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function identity(): HasOne
    {
        return $this->hasOne(StudentIdentity::class, 'student_id');
    }

    public function getFullNameAttribute(): string
    {
        return trim($this->first_name.' '.$this->last_name);
    }

    public function getDisplayNameAttribute(): string
    {
        $english = trim(($this->first_name_english ?? '').' '.($this->last_name_english ?? ''));

        return $english !== '' ? $english : $this->full_name;
    }

    public function hasSeparateIdentity(): bool
    {
        return $this->identity && $this->identity->login_type === 'separate';
    }

    public function usesSharedLogin(): bool
    {
        return ! $this->hasSeparateIdentity();
    }
}
