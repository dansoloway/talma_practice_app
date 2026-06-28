<?php

namespace App\Support;

use Illuminate\Http\Request;

class SignupLocale
{
    public const DEFAULT = 'he';

    /** @var list<string> */
    public const SUPPORTED = ['he', 'en', 'ar'];

    /** @var array<string, string> */
    public const LABELS = [
        'he' => 'עברית',
        'en' => 'English',
        'ar' => 'العربية',
    ];

    public static function normalize(?string $locale): string
    {
        $locale = strtolower((string) $locale);

        return in_array($locale, self::SUPPORTED, true) ? $locale : self::DEFAULT;
    }

    public static function resolve(Request $request): string
    {
        $locale = $request->query('lang') ?? $request->session()->get('signup_locale', self::DEFAULT);

        return self::normalize($locale);
    }

    public static function apply(Request $request): string
    {
        $locale = self::resolve($request);
        $request->session()->put('signup_locale', $locale);
        app()->setLocale($locale);

        return $locale;
    }

    public static function isRtl(?string $locale = null): bool
    {
        $locale = self::normalize($locale ?? app()->getLocale());

        return in_array($locale, ['he', 'ar'], true);
    }
}
