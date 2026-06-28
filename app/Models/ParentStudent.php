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
        'male' => ['he' => 'זכר', 'en' => 'Male', 'ar' => 'ذكر'],
        'female' => ['he' => 'נקבה', 'en' => 'Female', 'ar' => 'أنثى'],
        'other' => ['he' => 'אחר', 'en' => 'Other', 'ar' => 'آخر'],
    ];

    public const NATIVE_LANGUAGES = [
        'hebrew' => ['he' => 'עברית', 'en' => 'Hebrew', 'ar' => 'العبرية'],
        'arabic' => ['he' => 'ערבית', 'en' => 'Arabic', 'ar' => 'العربية'],
        'english' => ['he' => 'אנגלית', 'en' => 'English', 'ar' => 'الإنجليزية'],
        'other' => ['he' => 'אחר', 'en' => 'Other', 'ar' => 'أخرى'],
    ];

    public static function optionLabel(array|string $label, ?string $locale = null): string
    {
        if (! is_array($label)) {
            return $label;
        }

        $locale = \App\Support\SignupLocale::normalize($locale ?? app()->getLocale());

        return $label[$locale] ?? $label['en'] ?? (string) reset($label);
    }

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
        'native_language',
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

    public function age(): ?int
    {
        if (! $this->birth_date) {
            return null;
        }

        return (int) $this->birth_date->age;
    }
}
