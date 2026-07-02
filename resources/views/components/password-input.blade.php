@props([
    'name',
    'id' => null,
    'required' => false,
    'dir' => 'ltr',
    'value' => '',
    'inputClass' => 'w-full px-4 py-3 border border-gray-300 rounded-xl',
])

@php
    $inputId = $id ?? preg_replace('/[^a-zA-Z0-9_-]/', '-', $name);
    $fieldClasses = trim($inputClass . ($dir === 'ltr' ? ' signup-field-ltr' : ''));
    $toggleClass = str_contains($inputClass, 'rounded-lg')
        ? 'px-3 py-2 rounded-lg'
        : 'px-3 py-3 rounded-xl';
@endphp

<div class="password-field flex gap-2 items-stretch {{ $dir === 'ltr' ? 'signup-field-ltr' : '' }}" @if($dir === 'ltr') dir="ltr" @endif>
    <input
        type="password"
        name="{{ $name }}"
        id="{{ $inputId }}"
        value="{{ $value }}"
        @if($required) required @endif
        dir="{{ $dir }}"
        @if($dir === 'ltr') lang="en" @endif
        {{ $attributes->merge(['class' => trim('flex-1 min-w-0 ' . $fieldClasses)]) }}
    >
    <button
        type="button"
        class="shrink-0 border border-gray-300 text-gray-500 hover:text-gray-700 hover:bg-gray-100 transition-colors {{ $toggleClass }}"
        onclick="togglePasswordField(this)"
        aria-label="{{ __('parent-signup.show_password') }}"
        data-show-label="{{ __('parent-signup.show_password') }}"
        data-hide-label="{{ __('parent-signup.hide_password') }}"
    >
        <i class="fas fa-eye" aria-hidden="true"></i>
    </button>
</div>
