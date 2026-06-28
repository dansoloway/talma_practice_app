@extends('layouts.app')

@section('title', 'Complete your profile — '.$organization->display_name)

@section('content')
<div class="min-h-screen flex items-center justify-center py-12 px-4">
    <div class="w-full max-w-md">
        <div class="bg-white/90 backdrop-blur-sm rounded-2xl border border-gray-200/60 shadow-xl p-8">
            <div class="text-center mb-8">
                <h1 class="text-2xl font-bold text-gray-800 mb-2">One more step</h1>
                <p class="text-gray-600">
                    @if($context['mode'] === 'parent')
                        Please confirm voice recording consent for your family account. This covers every child you add now or later.
                    @else
                        Before you start practicing, please complete your learner profile and voice recording consent.
                    @endif
                </p>
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

            <form method="POST" action="{{ route('org.student.complete-voice-profile.submit', $organization) }}" class="space-y-6">
                @csrf

                @if($context['needs_student_fields'])
                    @if($context['mode'] === 'student')
                        <div>
                            <label for="age" class="block text-sm font-semibold text-gray-700 mb-2">Your age</label>
                            <input type="number" id="age" name="age" min="5" max="120" required
                                   class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-400 focus:border-blue-400"
                                   value="{{ old('age', $context['user']->age) }}">
                        </div>
                    @elseif($context['student'] && ! $context['student']->birth_date)
                        <div>
                            <label for="birth_year" class="block text-sm font-semibold text-gray-700 mb-2">Year of birth</label>
                            <input type="number" id="birth_year" name="birth_year" min="1990" max="{{ date('Y') }}" required
                                   class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-400 focus:border-blue-400"
                                   value="{{ old('birth_year', $context['student']->birth_date?->year) }}">
                        </div>
                    @endif

                    <div>
                        <label for="gender" class="block text-sm font-semibold text-gray-700 mb-2">Gender</label>
                        <select id="gender" name="gender" required
                                class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-400 focus:border-blue-400">
                            <option value="">Select...</option>
                            @foreach(\App\Models\ParentStudent::GENDERS as $value => $labels)
                                <option value="{{ $value }}" {{ old('gender', $context['student']?->gender ?? $context['user']->gender) === $value ? 'selected' : '' }}>
                                    {{ \App\Models\ParentStudent::optionLabel($labels) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="native_language" class="block text-sm font-semibold text-gray-700 mb-2">Native language</label>
                        <select id="native_language" name="native_language" required
                                class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-400 focus:border-blue-400">
                            <option value="">Select...</option>
                            @foreach(\App\Models\User::NATIVE_LANGUAGES as $value => $label)
                                <option value="{{ $value }}" {{ old('native_language', $context['student']?->native_language ?? $context['user']->native_language) === $value ? 'selected' : '' }}>
                                    {{ \App\Models\ParentStudent::optionLabel($label) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    @if($context['student'])
                        <p class="text-sm text-gray-500">
                            Updating profile for <strong>{{ $context['student']->display_name }}</strong>.
                        </p>
                    @endif
                @endif

                @if($context['needs_voice_consent'])
                    <div class="bg-blue-50 border border-blue-200 rounded-xl p-4">
                        <label class="flex items-start gap-3 cursor-pointer">
                            <input type="checkbox" name="voice_recording_consent" value="1" required
                                   class="mt-1 rounded border-gray-300 text-blue-600 focus:ring-blue-400"
                                   {{ old('voice_recording_consent') ? 'checked' : '' }}>
                            <span class="text-sm text-gray-700">
                                {{ config('app.voice_waiver_text') }}
                                @if($context['mode'] === 'parent')
                                    <span class="block mt-1 text-gray-500">This applies to every child on your account.</span>
                                @endif
                            </span>
                        </label>
                    </div>
                @endif

                <button type="submit" class="w-full px-6 py-3 bg-blue-600 text-white font-semibold rounded-xl hover:bg-blue-700 transition-colors">
                    Save and continue
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
