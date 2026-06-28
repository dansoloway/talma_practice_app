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
        'version',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    public static function getActiveByType(string $type): ?self
    {
        return self::where('type', $type)
            ->where('active', true)
            ->orderByDesc('version')
            ->first();
    }

    public static function getStudentSignupTerms(): ?self
    {
        return self::getActiveByType('student_signup');
    }
}
