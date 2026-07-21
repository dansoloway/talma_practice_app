<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TermsAndCondition extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'title',
        'content',
        'translations',
        'version',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
        'translations' => 'array',
    ];

    public static function getActiveByType(string $type): ?self
    {
        return self::where('type', $type)
            ->where('active', true)
            ->orderByDesc('version')
            ->first();
    }

    public const TYPE_STUDENT_SIGNUP = 'student_signup';

    public const TYPE_PRIVACY_POLICY = 'privacy_policy';

    public static function getStudentSignupTerms(): ?self
    {
        return self::getActiveByType(self::TYPE_STUDENT_SIGNUP);
    }

    public static function getPrivacyPolicy(): ?self
    {
        return self::getActiveByType(self::TYPE_PRIVACY_POLICY);
    }

    /**
     * @return array{title: string, content: string}
     */
    public function localized(?string $locale = null): array
    {
        $locale = \App\Support\SignupLocale::normalize($locale ?? app()->getLocale());
        $translations = $this->translations ?? [];

        if (isset($translations[$locale]['title'], $translations[$locale]['content'])) {
            return [
                'title' => $translations[$locale]['title'],
                'content' => $translations[$locale]['content'],
            ];
        }

        return [
            'title' => $this->title,
            'content' => $this->content,
        ];
    }
}
