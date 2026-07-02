@extends('layouts.app')

@php
    use App\Support\SignupLocale;
    $locale = app()->getLocale();
    $isRtl = SignupLocale::isRtl($locale);
@endphp

@section('html_lang', $locale)
@section('html_dir', $isRtl ? 'rtl' : 'ltr')

@section('title', __('parent-signup.page_title', ['org' => $organization->display_name]))

@section('content')
<div class="min-h-screen py-12 px-4">
    <div class="w-full max-w-2xl mx-auto">
        <div class="bg-white/90 backdrop-blur-sm rounded-2xl border border-gray-200/60 shadow-xl p-8">
            <x-signup-locale-switcher />

            <div class="text-center mb-8">
                <h1 class="text-3xl font-bold text-gray-800 mb-2">{{ __('parent-signup.heading', ['org' => $organization->display_name]) }}</h1>
                <p class="text-gray-600">{{ __('parent-signup.subtitle') }}</p>
            </div>

            @if($errors->any())
                <div class="bg-red-50 border border-red-200 text-red-800 p-4 rounded-xl mb-6">
                    <ul class="list-disc list-inside space-y-1">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form id="parent-signup-form" method="POST" action="{{ route('org.student.register.submit', $organization) }}" class="space-y-8" novalidate>
                @csrf

                <section class="space-y-4">
                    <h2 class="text-lg font-semibold text-gray-800">{{ __('parent-signup.parent_section') }}</h2>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">{{ __('parent-signup.hebrew_name') }}</label>
                        <input type="text" name="hebrew_name" value="{{ old('hebrew_name') }}" required
                               @if($isRtl) dir="rtl" class="w-full px-4 py-3 border border-gray-300 rounded-xl signup-field-rtl" @else class="w-full px-4 py-3 border border-gray-300 rounded-xl" @endif>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">{{ __('parent-signup.english_name') }}</label>
                        <input type="text" name="name" value="{{ old('name') }}" required dir="ltr" lang="en"
                               class="w-full px-4 py-3 border border-gray-300 rounded-xl signup-field-ltr">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">{{ __('parent-signup.id_number') }}</label>
                        <input type="text" name="id_number" value="{{ old('id_number') }}" required dir="ltr" lang="en" inputmode="numeric"
                               class="w-full px-4 py-3 border border-gray-300 rounded-xl signup-field-ltr">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">{{ __('parent-signup.email') }}</label>
                        <input type="email" name="email" value="{{ old('email') }}" required dir="ltr" lang="en"
                               class="w-full px-4 py-3 border border-gray-300 rounded-xl signup-field-ltr">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">{{ __('parent-signup.password') }}</label>
                        <x-password-input name="password" required />
                        <p class="mt-1 text-sm text-gray-500">{{ __('parent-signup.password_hint') }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">{{ __('parent-signup.confirm_password') }}</label>
                        <x-password-input name="password_confirmation" required />
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">{{ __('parent-signup.phone') }}</label>
                        <div class="flex gap-2 signup-field-ltr" dir="ltr">
                            <select name="phone_prefix" required dir="ltr" lang="en"
                                    class="w-28 px-3 py-3 border border-gray-300 rounded-xl signup-field-ltr">
                                @foreach(['050','051','052','053','054','055','056','057','058','059','072','073','074','075','076','077','078','079'] as $p)
                                    <option value="{{ $p }}" {{ old('phone_prefix', '050') == $p ? 'selected' : '' }}>{{ $p }}</option>
                                @endforeach
                            </select>
                            <input type="tel" name="phone_rest" value="{{ old('phone_rest') }}" required inputmode="numeric" maxlength="7" dir="ltr" lang="en"
                                   class="flex-1 px-4 py-3 border border-gray-300 rounded-xl signup-field-ltr"
                                   placeholder="{{ __('parent-signup.phone_placeholder') }}" oninput="this.value=this.value.replace(/\D/g,'')">
                        </div>
                    </div>
                    @if($cities->isNotEmpty())
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">{{ __('parent-signup.city_optional') }}</label>
                            <select name="city_id" class="w-full px-4 py-3 border border-gray-300 rounded-xl">
                                <option value="">{{ __('parent-signup.select_city') }}</option>
                                @foreach($cities as $city)
                                    <option value="{{ $city->id }}" {{ old('city_id') == $city->id ? 'selected' : '' }}>
                                        {{ ($isRtl && $city->hebrew_name) ? $city->hebrew_name : $city->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                </section>

                <section class="space-y-4 border-t pt-6" id="children-section">
                    <div class="flex justify-between items-center">
                        <h2 class="text-lg font-semibold text-gray-800">{{ __('parent-signup.children') }}</h2>
                        <button type="button" id="add-child-btn" class="text-sm text-blue-600 hover:text-blue-800 font-medium">{{ __('parent-signup.add_child') }}</button>
                    </div>
                    @for ($i = 0; $i < 8; $i++)
                        @include('student.auth.partials.parent-child-row', ['index' => $i, 'hidden' => $i > 0])
                    @endfor
                </section>

                @if($terms)
                    <div class="border-t pt-4">
                        <label class="flex items-start gap-3 cursor-pointer">
                            <input type="checkbox" id="terms_accepted" name="terms_accepted" value="1" required class="mt-1 rounded border-gray-300 text-blue-600" {{ old('terms_accepted') ? 'checked' : '' }}>
                            <span class="text-sm text-gray-700">
                                {{ __('parent-signup.terms_prefix') }}
                                <button type="button" onclick="openTermsModal()" class="text-blue-600 hover:underline font-medium">{{ __('parent-signup.terms_link') }}</button>
                            </span>
                        </label>
                    </div>
                    <x-terms-modal :terms="$terms" :locale="$locale" />
                @endif

                @if($organization->retain_voice_recordings)
                    <div class="bg-blue-50 border border-blue-200 rounded-xl p-4">
                        <label class="flex items-start gap-3 cursor-pointer">
                            <input type="checkbox" name="voice_recording_consent" value="1" required
                                   class="mt-1 rounded border-gray-300 text-blue-600 focus:ring-blue-400"
                                   {{ old('voice_recording_consent') ? 'checked' : '' }}>
                            <span class="text-sm text-gray-700">
                                {{ __('parent-signup.voice_waiver') }}
                                <span class="block mt-1 text-gray-500">{{ __('parent-signup.voice_applies_all') }}</span>
                            </span>
                        </label>
                    </div>
                @endif

                <button type="submit" class="w-full px-6 py-3 bg-blue-600 text-white font-semibold rounded-xl hover:bg-blue-700 transition-colors">
                    {{ __('parent-signup.create_account') }}
                </button>
            </form>

            <p class="mt-6 text-center text-gray-600">
                {{ __('parent-signup.already_have') }}
                <a href="{{ route('org.student.login', ['organization' => $organization, 'lang' => $locale]) }}" class="text-blue-600 hover:text-blue-700 font-medium">{{ __('parent-signup.sign_in') }}</a>
            </p>
        </div>
    </div>
</div>

@include('student.auth.partials.parent-signup-scripts')
@endsection
