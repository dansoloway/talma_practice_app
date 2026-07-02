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
@endphp

<div class="relative">
    <input
        type="password"
        name="{{ $name }}"
        id="{{ $inputId }}"
        value="{{ $value }}"
        @if($required) required @endif
        dir="{{ $dir }}"
        {{ $attributes->merge(['class' => trim($inputClass . ' pe-12')]) }}
    >
    <button
        type="button"
        class="absolute end-3 top-1/2 -translate-y-1/2 text-gray-500 hover:text-gray-700 p-2 rounded-lg hover:bg-gray-100 transition-colors"
        onclick="togglePasswordField(this)"
        aria-label="{{ __('parent-signup.show_password') }}"
        data-show-label="{{ __('parent-signup.show_password') }}"
        data-hide-label="{{ __('parent-signup.hide_password') }}"
    >
        <i class="fas fa-eye" aria-hidden="true"></i>
    </button>
</div>
