@php
    use App\Support\SignupLocale;
    $currentLocale = app()->getLocale();
@endphp
<div class="flex justify-end gap-2 mb-6" dir="ltr">
    @foreach(SignupLocale::LABELS as $code => $label)
        <a href="{{ request()->fullUrlWithQuery(['lang' => $code]) }}"
           class="px-3 py-1.5 rounded-lg text-sm font-medium transition-colors {{ $currentLocale === $code ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}"
           lang="{{ $code }}">
            {{ $label }}
        </a>
    @endforeach
</div>
