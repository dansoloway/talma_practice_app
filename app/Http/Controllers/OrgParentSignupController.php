<?php

namespace App\Http\Controllers;

use App\Helpers\PhoneRules;
use App\Models\City;
use App\Models\Organization;
use App\Models\StudentIdentity;
use App\Models\TermsAndCondition;
use App\Models\User;
use App\Services\LearnerSessionService;
use App\Services\StudentProfileService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Validator as ValidatorInstance;
use App\Support\SignupLocale;
use Illuminate\View\View;

class OrgParentSignupController extends Controller
{
    public function __construct(
        protected StudentProfileService $studentService,
        protected LearnerSessionService $learnerSession,
    ) {}

    public function showRegister(Organization $organization): View
    {
        SignupLocale::apply(request());

        $terms = TermsAndCondition::getStudentSignupTerms();
        $privacyPolicy = TermsAndCondition::getPrivacyPolicy();
        $cities = City::orderBy('name')->get();

        return view('student.auth.parent-register', compact('organization', 'terms', 'privacyPolicy', 'cities'));
    }

    public function register(Request $request, Organization $organization): RedirectResponse
    {
        SignupLocale::apply($request);

        if (! $organization->allow_self_registration || $organization->access_mode !== 'restricted' || ! $organization->usesParentSignup()) {
            abort(404);
        }

        $terms = TermsAndCondition::getStudentSignupTerms();
        $privacyPolicy = TermsAndCondition::getPrivacyPolicy();
        $consentRules = [];
        if ($terms) {
            $consentRules['terms_accepted'] = ['required', 'accepted'];
        }
        if ($privacyPolicy) {
            $consentRules['privacy_policy_read'] = ['required', 'accepted'];
        }
        $voiceRules = $organization->retain_voice_recordings
            ? ['voice_recording_consent' => ['required', 'accepted']]
            : [];

        $rules = array_merge([
            'name' => ['required', 'string', 'max:255'],
            'hebrew_name' => ['required', 'string', 'max:255'],
            'id_number' => ['required', 'string', 'max:20'],
            'email' => ['required', 'email', 'lowercase', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)],
            'phone_number' => [
                'required',
                'string',
                function ($attribute, $value, $fail) {
                    $cleaned = preg_replace('/\D/', '', (string) $value);
                    $withLeadingZero = strlen($cleaned) === 9 ? '0'.$cleaned : $cleaned;
                    if (! PhoneRules::isValidIsraeliPhone($withLeadingZero)) {
                        $fail(__('parent-signup.validation.phone_invalid'));
                    }
                },
            ],
            'city_id' => ['nullable', 'exists:cities,id'],
            'students' => ['required', 'array', 'min:1'],
            'students.*.first_name' => ['required', 'string', 'max:255'],
            'students.*.last_name' => ['required', 'string', 'max:255'],
            'students.*.first_name_english' => ['required', 'string', 'max:255'],
            'students.*.last_name_english' => ['required', 'string', 'max:255'],
            'students.*.birth_year' => ['required', 'integer', 'min:1990', 'max:'.(int) date('Y')],
            'students.*.grade' => ['required', 'integer', 'min:1', 'max:12'],
            'students.*.gender' => ['required', 'in:male,female,other'],
            'students.*.native_language' => ['required', 'in:hebrew,arabic,english,other'],
            'students.*.login_type' => ['required', 'in:shared,separate'],
            'students.*.contact_type' => ['nullable', 'in:email,phone'],
            'students.*.email' => ['nullable', 'email', 'lowercase'],
            'students.*.password' => ['nullable', 'confirmed', Password::min(8)],
            'students.*.phone_prefix' => ['nullable', 'string', 'max:5'],
            'students.*.phone_rest' => ['nullable', 'string', 'max:10'],
        ], $consentRules, $voiceRules);

        $prefix = $request->input('phone_prefix', '');
        $rest = preg_replace('/\D/', '', (string) $request->input('phone_rest', ''));
        if ($prefix !== '' || $rest !== '') {
            $request->merge(['phone_number' => $prefix.$rest]);
        }

        $studentsInput = $request->input('students', []);
        foreach ($studentsInput as $i => &$s) {
            if (($s['login_type'] ?? '') === 'separate' && ($s['contact_type'] ?? '') === 'phone') {
                $sp = $s['phone_prefix'] ?? '';
                $sr = preg_replace('/\D/', '', (string) ($s['phone_rest'] ?? ''));
                $studentsInput[$i]['phone_number'] = $sp.$sr;
            }
        }
        unset($s);
        $request->merge(['students' => $studentsInput]);

        $validator = Validator::make($request->all(), $rules, [
            'terms_accepted.accepted' => __('parent-signup.validation.terms_accepted'),
            'privacy_policy_read.accepted' => __('parent-signup.validation.privacy_policy_read'),
        ]);
        $validator->after(fn ($v) => $this->validateParentSignupIdentities($v, $request));
        $validated = $validator->validate();

        $normalizedPhone = PhoneRules::normalize($validated['phone_number']);
        $termsAcceptedAt = now();
        $termsVersion = $terms?->version;
        $privacyPolicyVersion = $privacyPolicy?->version;

        DB::beginTransaction();
        try {
            $parent = User::create([
                'name' => $validated['name'],
                'hebrew_name' => $validated['hebrew_name'],
                'email' => $validated['email'],
                'id_number' => $validated['id_number'],
                'phone_number' => $normalizedPhone,
                'city_id' => $validated['city_id'] ?? null,
                'password' => Hash::make($validated['password']),
                'role' => User::ROLE_PARENT,
                'is_active' => true,
                'terms_accepted_at' => $terms ? $termsAcceptedAt : null,
                'terms_version' => $termsVersion,
                'privacy_policy_read_at' => $privacyPolicy ? $termsAcceptedAt : null,
                'privacy_policy_version' => $privacyPolicyVersion,
                'voice_recording_consented_at' => ($organization->retain_voice_recordings && ! empty($validated['voice_recording_consent']))
                    ? $termsAcceptedAt
                    : null,
            ]);

            $organization->users()->syncWithoutDetaching([
                $parent->id => ['role' => 'parent'],
            ]);

            foreach ($validated['students'] as $studentData) {
                if (($studentData['login_type'] ?? '') === 'separate') {
                    $contactType = $studentData['contact_type'] ?? 'email';
                    if ($contactType === 'email' && empty($studentData['email'])) {
                        continue;
                    }
                    if ($contactType === 'phone' && empty($studentData['phone_number'])) {
                        continue;
                    }
                    if (empty($studentData['password'])) {
                        throw ValidationException::withMessages([
                            'students.0.password' => __('parent-signup.validation.child_password_required'),
                        ]);
                    }
                }

                $birthDate = ! empty($studentData['birth_year'])
                    ? $studentData['birth_year'].'-06-01'
                    : null;

                $payload = [
                    'first_name' => $studentData['first_name'],
                    'last_name' => $studentData['last_name'],
                    'first_name_english' => $studentData['first_name_english'],
                    'last_name_english' => $studentData['last_name_english'],
                    'birth_date' => $birthDate,
                    'grade' => $studentData['grade'] ?? null,
                    'gender' => $studentData['gender'] ?? null,
                    'native_language' => $studentData['native_language'] ?? null,
                    'login_type' => $studentData['login_type'],
                    'terms_accepted_at' => $terms ? $termsAcceptedAt : null,
                    'terms_version' => $termsVersion,
                    'privacy_policy_read_at' => $privacyPolicy ? $termsAcceptedAt : null,
                    'privacy_policy_version' => $privacyPolicyVersion,
                ];

                if (($studentData['login_type'] ?? '') === 'separate') {
                    $contactType = $studentData['contact_type'] ?? 'email';
                    if ($contactType === 'email') {
                        $payload['email'] = strtolower(trim($studentData['email']));
                        $payload['password'] = $studentData['password'];
                    } else {
                        $normalizedStudentPhone = PhoneRules::normalize($studentData['phone_number']);
                        $payload['phone_number'] = $normalizedStudentPhone;
                        $payload['email'] = preg_replace('/\D/', '', $normalizedStudentPhone).'@practice-pal.phone';
                        $payload['password'] = $studentData['password'];
                    }
                }

                $this->studentService->createStudent($parent, $organization, $payload);
            }

            $this->studentService->propagateVoiceConsent($parent);

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        Auth::guard('admin')->login($parent);
        $request->session()->regenerate();

        return $this->learnerSession->redirectAfterAuth($parent, $organization)
            ->with('success', __('parent-signup.welcome'));
    }

    protected function validateParentSignupIdentities(ValidatorInstance $v, Request $request): void
    {
        $students = $request->input('students', []);
        $parentEmail = strtolower(trim((string) ($request->input('email') ?? '')));
        $parentPhoneNorm = PhoneRules::normalize((string) ($request->input('phone_number') ?? ''));

        if ($parentPhoneNorm !== '' && User::where('phone_number', $parentPhoneNorm)->exists()) {
            $v->errors()->add('phone_rest', __('parent-signup.validation.phone_registered'));
        }

        $studentEmailEntries = [];
        $studentPhoneEntries = [];

        foreach ($students as $i => $s) {
            if (($s['login_type'] ?? '') !== 'separate') {
                continue;
            }

            if (empty($s['password'])) {
                $v->errors()->add("students.{$i}.password", __('parent-signup.validation.child_password_required'));
            }

            $contactType = $s['contact_type'] ?? 'email';

            if ($contactType === 'email') {
                $email = strtolower(trim((string) ($s['email'] ?? '')));
                if ($email === '') {
                    $v->errors()->add("students.{$i}.email", __('parent-signup.validation.child_email_required'));
                    continue;
                }
                if ($email === $parentEmail) {
                    $v->errors()->add("students.{$i}.email", __('parent-signup.validation.child_email_different'));
                    continue;
                }
                if (User::where('email', $email)->exists()) {
                    $v->errors()->add("students.{$i}.email", __('parent-signup.validation.child_email_registered'));
                }
                if (StudentIdentity::where('email', $email)->exists()) {
                    $v->errors()->add("students.{$i}.email", __('parent-signup.validation.child_email_taken'));
                }
                $studentEmailEntries[] = ['index' => $i, 'email' => $email];
            } else {
                $phone = trim((string) ($s['phone_number'] ?? ''));
                if ($phone === '') {
                    $v->errors()->add("students.{$i}.phone_rest", __('parent-signup.validation.child_phone_required'));
                    continue;
                }
                $cleaned = preg_replace('/\D/', '', $phone);
                $withLeadingZero = strlen($cleaned) === 9 ? '0'.$cleaned : $cleaned;
                if (! PhoneRules::isValidIsraeliPhone($withLeadingZero)) {
                    $v->errors()->add("students.{$i}.phone_rest", __('parent-signup.validation.child_phone_invalid'));
                    continue;
                }
                $studentPhoneNorm = PhoneRules::normalize($phone);
                if ($studentPhoneNorm !== '' && $studentPhoneNorm === $parentPhoneNorm) {
                    $v->errors()->add("students.{$i}.phone_rest", __('parent-signup.validation.child_phone_different'));
                    continue;
                }
                if (User::where('phone_number', $studentPhoneNorm)->exists()) {
                    $v->errors()->add("students.{$i}.phone_rest", __('parent-signup.validation.child_phone_registered'));
                }
                if (StudentIdentity::where('phone_number', $studentPhoneNorm)->exists()) {
                    $v->errors()->add("students.{$i}.phone_rest", __('parent-signup.validation.child_phone_taken'));
                }
                $studentPhoneEntries[] = ['index' => $i, 'phone' => $studentPhoneNorm];
            }
        }

        foreach (collect($studentEmailEntries)->groupBy('email') as $indices) {
            if ($indices->count() > 1) {
                foreach ($indices as $entry) {
                    $v->errors()->add("students.{$entry['index']}.email", __('parent-signup.validation.child_email_duplicate'));
                }
            }
        }

        foreach (collect($studentPhoneEntries)->groupBy('phone') as $indices) {
            if ($indices->count() > 1) {
                foreach ($indices as $entry) {
                    $v->errors()->add("students.{$entry['index']}.phone_rest", __('parent-signup.validation.child_phone_duplicate'));
                }
            }
        }
    }
}
