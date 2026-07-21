<?php

namespace App\Support;

use App\Models\Organization;
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

    public static function sessionKey(?Organization $org = null): string
    {
        if ($org instanceof Organization && $org->usesParentSignup()) {
            return 'signup_locale.'.$org->slug;
        }

        return 'signup_locale';
    }

    public static function organizationFromRequest(Request $request): ?Organization
    {
        $org = $request->attributes->get('currentOrganization');
        if ($org instanceof Organization) {
            return $org;
        }

        $routeOrg = $request->route('organization');
        if ($routeOrg instanceof Organization) {
            return $routeOrg;
        }

        return null;
    }

    public static function resolve(Request $request): string
    {
        $org = self::organizationFromRequest($request);
        $sessionKey = self::sessionKey($org);
        $locale = $request->query('lang') ?? $request->session()->get($sessionKey, self::DEFAULT);

        return self::normalize($locale);
    }

    public static function apply(Request $request): string
    {
        $org = self::organizationFromRequest($request);
        $sessionKey = self::sessionKey($org);
        $locale = self::resolve($request);
        $request->session()->put($sessionKey, $locale);
        app()->setLocale($locale);

        return $locale;
    }

    public static function shouldApplyStudentLocale(?Request $request = null): bool
    {
        $request = $request ?? request();
        $org = $request->attributes->get('currentOrganization');

        if ($org instanceof Organization && $org->usesParentSignup()) {
            return true;
        }

        return $request->session()->has('signup_locale');
    }

    /** @return array<string, mixed> */
    public static function gameStrings(?string $locale = null): array
    {
        $locale = self::normalize($locale ?? app()->getLocale());

        return trans('student-portal.games', [], $locale);
    }

    public static function isRtl(?string $locale = null): bool
    {
        $locale = self::normalize($locale ?? app()->getLocale());

        return in_array($locale, ['he', 'ar'], true);
    }

    public static function gradeLabel(int $grade, ?string $locale = null): string
    {
        $locale = self::normalize($locale ?? app()->getLocale());
        $key = "parent-signup.child.grade_options.{$grade}";
        $label = trans($key, [], $locale);

        if ($label !== $key) {
            return $label;
        }

        return trans('parent-signup.child.grade_option', ['grade' => $grade], $locale);
    }

    public static function activityLabel(string $type, ?string $fallback = null): string
    {
        $key = "student-portal.activities.{$type}";
        $label = trans($key);

        if ($label !== $key) {
            return $label;
        }

        return $fallback ?? $type;
    }

    public static function countLabel(string $unit, int $count): string
    {
        return trans_choice("student-portal.units.{$unit}", $count, ['count' => $count]);
    }

    public static function normalizeActivityTitle(string $type, string $title, string $lessonTitle, ?string $fallback = null): string
    {
        $fallback = $fallback ?? self::activityLabel($type);
        $lessonTitleEscaped = preg_quote(trim($lessonTitle), '/');
        $trimmed = trim($title);

        $patterns = [
            'matching' => '/^'.$lessonTitleEscaped.'\s+Matching\s+Game\s+\d+$/i',
            'flashcard' => '/^'.$lessonTitleEscaped.'\s+Flashcards\s+\d+$/i',
            'spelling' => '/^'.$lessonTitleEscaped.'\s+Spelling\s+Practice\s+\d+$/i',
        ];

        if (isset($patterns[$type]) && preg_match($patterns[$type], $trimmed)) {
            return $fallback;
        }

        return $trimmed !== '' ? $trimmed : $fallback;
    }

    public static function flowStepLabel(\App\DataTransferObjects\LessonFlowStep $step, string $lessonTitle): string
    {
        if (in_array($step->type, ['vocabulary', 'prompts', 'matching', 'flashcard', 'spelling', 'clause_exercise', 'true_false'], true)) {
            return self::activityLabel($step->type);
        }

        return self::normalizeActivityTitle($step->type, $step->label, $lessonTitle);
    }
}
