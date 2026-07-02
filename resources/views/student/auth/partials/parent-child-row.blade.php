@php
    use App\Support\SignupLocale;
    $s = old('students.'.$index, [
        'first_name' => '', 'last_name' => '', 'first_name_english' => '', 'last_name_english' => '',
        'birth_year' => '', 'grade' => '', 'gender' => '', 'native_language' => '', 'login_type' => 'shared',
        'contact_type' => 'email', 'email' => '', 'phone_prefix' => '050', 'phone_rest' => '',
    ]);
    $locale = app()->getLocale();
    $isRtl = SignupLocale::isRtl($locale);
@endphp
<div class="child-row p-4 bg-gray-50 rounded-xl space-y-3 {{ $hidden ? 'hidden' : '' }}" data-index="{{ $index }}">
    <div class="flex justify-between items-center">
        <span class="text-sm font-semibold text-gray-800">{{ __('parent-signup.child.label', ['number' => $index + 1]) }}</span>
        @if($index > 0)
            <button type="button" class="remove-child text-red-600 text-sm hover:underline">{{ __('parent-signup.child.remove') }}</button>
        @endif
    </div>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        <div>
            <label class="block text-sm mb-1">{{ __('parent-signup.child.first_name_hebrew') }}</label>
            <input type="text" name="students[{{ $index }}][first_name]" value="{{ $s['first_name'] ?? '' }}" required
                   @if($isRtl) dir="rtl" class="w-full px-3 py-2 border border-gray-300 rounded-lg signup-field-rtl" @else class="w-full px-3 py-2 border border-gray-300 rounded-lg" @endif>
        </div>
        <div>
            <label class="block text-sm mb-1">{{ __('parent-signup.child.last_name_hebrew') }}</label>
            <input type="text" name="students[{{ $index }}][last_name]" value="{{ $s['last_name'] ?? '' }}" required
                   @if($isRtl) dir="rtl" class="w-full px-3 py-2 border border-gray-300 rounded-lg signup-field-rtl" @else class="w-full px-3 py-2 border border-gray-300 rounded-lg" @endif>
        </div>
    </div>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        <div>
            <label class="block text-sm mb-1">{{ __('parent-signup.child.first_name_english') }}</label>
            <input type="text" name="students[{{ $index }}][first_name_english]" value="{{ $s['first_name_english'] ?? '' }}" required dir="ltr" lang="en"
                   class="w-full px-3 py-2 border border-gray-300 rounded-lg signup-field-ltr">
        </div>
        <div>
            <label class="block text-sm mb-1">{{ __('parent-signup.child.last_name_english') }}</label>
            <input type="text" name="students[{{ $index }}][last_name_english]" value="{{ $s['last_name_english'] ?? '' }}" required dir="ltr" lang="en"
                   class="w-full px-3 py-2 border border-gray-300 rounded-lg signup-field-ltr">
        </div>
    </div>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        <div>
            <label class="block text-sm mb-1">{{ __('parent-signup.child.birth_year') }}</label>
            <input type="number" name="students[{{ $index }}][birth_year]" value="{{ $s['birth_year'] ?? '' }}" min="1990" max="{{ date('Y') }}" required dir="ltr" lang="en"
                   class="w-full px-3 py-2 border border-gray-300 rounded-lg signup-field-ltr">
        </div>
        <div>
            <label class="block text-sm mb-1">{{ __('parent-signup.child.grade') }}</label>
            <select name="students[{{ $index }}][grade]" required
                    @if($locale === 'en') dir="ltr" lang="en" class="w-full px-3 py-2 border border-gray-300 rounded-lg signup-field-ltr"
                    @elseif($locale === 'he') dir="rtl" class="w-full px-3 py-2 border border-gray-300 rounded-lg signup-field-rtl"
                    @else class="w-full px-3 py-2 border border-gray-300 rounded-lg" @endif>
                <option value="">{{ __('parent-signup.child.select_grade') }}</option>
                @for($g = 1; $g <= 12; $g++)
                    <option value="{{ $g }}" {{ ($s['grade'] ?? '') == $g ? 'selected' : '' }}>{{ SignupLocale::gradeLabel($g, $locale) }}</option>
                @endfor
            </select>
        </div>
    </div>
    <div>
        <label class="block text-sm mb-1">{{ __('parent-signup.child.gender') }}</label>
        <select name="students[{{ $index }}][gender]" required class="w-full px-3 py-2 border border-gray-300 rounded-lg">
            <option value="">{{ __('parent-signup.child.select_gender') }}</option>
            @foreach(\App\Models\ParentStudent::GENDERS as $val => $labels)
                <option value="{{ $val }}" {{ ($s['gender'] ?? '') == $val ? 'selected' : '' }}>{{ \App\Models\ParentStudent::optionLabel($labels, $locale) }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="block text-sm mb-1">{{ __('parent-signup.child.native_language') }}</label>
        <select name="students[{{ $index }}][native_language]" required class="w-full px-3 py-2 border border-gray-300 rounded-lg">
            <option value="">{{ __('parent-signup.child.select_native_language') }}</option>
            @foreach(\App\Models\ParentStudent::NATIVE_LANGUAGES as $val => $label)
                <option value="{{ $val }}" {{ ($s['native_language'] ?? '') == $val ? 'selected' : '' }}>{{ \App\Models\ParentStudent::optionLabel($label, $locale) }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="block text-sm font-semibold mb-2">{{ __('parent-signup.child.login_type') }}</label>
        <div class="flex flex-col sm:flex-row gap-3">
            <label class="flex items-center gap-2">
                <input type="radio" name="students[{{ $index }}][login_type]" value="shared" {{ ($s['login_type'] ?? 'shared') === 'shared' ? 'checked' : '' }}>
                <span class="text-sm">{{ __('parent-signup.child.login_shared') }}</span>
            </label>
            <label class="flex items-center gap-2">
                <input type="radio" name="students[{{ $index }}][login_type]" value="separate" {{ ($s['login_type'] ?? '') === 'separate' ? 'checked' : '' }}>
                <span class="text-sm">{{ __('parent-signup.child.login_separate') }}</span>
            </label>
        </div>
    </div>
    <div class="student-separate-fields space-y-3 pt-2 border-t border-gray-200" style="{{ ($s['login_type'] ?? '') === 'separate' ? '' : 'display:none' }}">
        <p class="text-sm text-gray-600">{{ __('parent-signup.child.separate_hint') }}</p>
        <div class="flex gap-4">
            <label class="flex items-center gap-2">
                <input type="radio" name="students[{{ $index }}][contact_type]" value="email" {{ ($s['contact_type'] ?? 'email') === 'email' ? 'checked' : '' }} class="student-contact-type">
                <span class="text-sm">{{ __('parent-signup.child.contact_email') }}</span>
            </label>
            <label class="flex items-center gap-2">
                <input type="radio" name="students[{{ $index }}][contact_type]" value="phone" {{ ($s['contact_type'] ?? '') === 'phone' ? 'checked' : '' }} class="student-contact-type">
                <span class="text-sm">{{ __('parent-signup.child.contact_phone') }}</span>
            </label>
        </div>
        <div class="student-contact-email" style="{{ ($s['contact_type'] ?? 'email') === 'email' ? '' : 'display:none' }}">
            <label class="block text-sm mb-1">{{ __('parent-signup.child.child_email') }}</label>
            <input type="email" name="students[{{ $index }}][email]" value="{{ $s['email'] ?? '' }}" dir="ltr" lang="en"
                   class="w-full px-3 py-2 border border-gray-300 rounded-lg student-email-input signup-field-ltr">
        </div>
        <div class="student-contact-phone flex gap-2 signup-field-ltr" dir="ltr" style="{{ ($s['contact_type'] ?? '') === 'phone' ? '' : 'display:none' }}">
            <select name="students[{{ $index }}][phone_prefix]" dir="ltr" lang="en"
                    class="w-28 px-3 py-2 border border-gray-300 rounded-lg student-phone-prefix signup-field-ltr">
                @foreach(['050','051','052','053','054','055','056','057','058','059'] as $p)
                    <option value="{{ $p }}" {{ ($s['phone_prefix'] ?? '050') == $p ? 'selected' : '' }}>{{ $p }}</option>
                @endforeach
            </select>
            <input type="tel" name="students[{{ $index }}][phone_rest]" value="{{ $s['phone_rest'] ?? '' }}" maxlength="7" dir="ltr" lang="en" inputmode="numeric"
                   class="flex-1 px-3 py-2 border border-gray-300 rounded-lg student-phone-rest signup-field-ltr" oninput="this.value=this.value.replace(/\D/g,'')">
        </div>
        <div>
            <label class="block text-sm mb-1">{{ __('parent-signup.child.child_password') }}</label>
            <x-password-input name="students[{{ $index }}][password]" input-class="w-full px-3 py-2 border border-gray-300 rounded-lg" />
        </div>
        <div>
            <label class="block text-sm mb-1">{{ __('parent-signup.child.confirm_child_password') }}</label>
            <x-password-input name="students[{{ $index }}][password_confirmation]" input-class="w-full px-3 py-2 border border-gray-300 rounded-lg" />
        </div>
    </div>
</div>
