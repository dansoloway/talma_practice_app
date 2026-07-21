@extends('layouts.app')

@php
    use App\Support\SignupLocale;
    $usesSignupLocale = $organization->usesParentSignup();
    $locale = $usesSignupLocale ? app()->getLocale() : 'en';
    $isRtl = $usesSignupLocale && SignupLocale::isRtl($locale);
@endphp

@section('html_lang', $locale)
@section('html_dir', $isRtl ? 'rtl' : 'ltr')

@section('title', $usesSignupLocale
    ? __('parent-signup.login.page_title', ['org' => $organization->display_name])
    : 'Login — ' . $organization->display_name)

@section('content')
<div class="min-h-screen flex items-center justify-center py-12 px-4">
    <div class="w-full max-w-md">
        <div class="bg-white/90 backdrop-blur-sm rounded-2xl border border-gray-200/60 shadow-xl p-8">
            <div class="text-center mb-8">
                <h1 class="text-3xl font-bold text-gray-800 mb-2">{{ $organization->display_name }}</h1>
                <p class="text-gray-600">
                    @if($usesSignupLocale)
                        {{ __('parent-signup.login.subtitle') }}
                    @else
                        Sign in to access your courses
                    @endif
                </p>
            </div>

            @if(session('success'))
                <div class="bg-green-50 border border-green-200 text-green-800 p-4 rounded-xl mb-6">
                    {{ session('success') }}
                </div>
            @endif

            @if($errors->any())
                <div class="bg-red-50 border border-red-200 text-red-800 p-4 rounded-xl mb-6">
                    <ul class="list-disc list-inside space-y-1">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('org.student.login.submit', $organization) }}" class="space-y-6">
                @csrf
                <div>
                    <label for="email" class="block text-sm font-semibold text-gray-700 mb-2">
                        @if($organization->usesParentSignup())
                            {{ __('parent-signup.login.email_or_phone') }}
                        @else
                            {{ $usesSignupLocale ? __('parent-signup.login.email') : 'Email' }}
                        @endif
                    </label>
                    <input type="text" id="email" name="email" required autofocus
                           class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-400 focus:border-blue-400 signup-field-ltr"
                           value="{{ old('email') }}" dir="ltr" lang="en">
                </div>
                <div>
                    <label for="password" class="block text-sm font-semibold text-gray-700 mb-2">
                        {{ $usesSignupLocale ? __('parent-signup.login.password') : 'Password' }}
                    </label>
                    <input type="password" id="password" name="password" required dir="ltr" lang="en"
                           class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-400 focus:border-blue-400 signup-field-ltr">
                </div>
                <div>
                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="checkbox" name="remember" value="1" {{ old('remember') ? 'checked' : '' }}
                               class="w-5 h-5 text-blue-600 border-gray-300 rounded">
                        <span class="text-gray-700 font-medium">
                            {{ $usesSignupLocale ? __('parent-signup.login.remember_me') : 'Remember me' }}
                        </span>
                    </label>
                </div>
                <button type="submit" class="w-full px-6 py-3 bg-blue-600 text-white font-semibold rounded-xl hover:bg-blue-700 transition-colors">
                    {{ $usesSignupLocale ? __('parent-signup.login.submit') : 'Sign In' }}
                </button>
            </form>

            @if($organization->allow_self_registration)
                <p class="mt-6 text-center text-gray-600">
                    @if($usesSignupLocale)
                        {{ __('parent-signup.login.no_account') }}
                        <a href="{{ route('org.student.register', ['organization' => $organization, 'lang' => $locale]) }}"
                           class="text-blue-600 hover:text-blue-700 font-medium">{{ __('parent-signup.login.create_one') }}</a>
                    @else
                        Don't have an account?
                        <a href="{{ route('org.student.register', $organization) }}" class="text-blue-600 hover:text-blue-700 font-medium">Create one</a>
                    @endif
                </p>
            @endif
        </div>
    </div>
</div>
@endsection
