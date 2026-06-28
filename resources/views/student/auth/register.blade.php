@extends('layouts.app')

@section('title', 'Register — ' . $organization->display_name)

@section('content')
<div class="min-h-screen flex items-center justify-center py-12 px-4">
    <div class="w-full max-w-md">
        <div class="bg-white/90 backdrop-blur-sm rounded-2xl border border-gray-200/60 shadow-xl p-8">
            <div class="text-center mb-8">
                <h1 class="text-3xl font-bold text-gray-800 mb-2">Join {{ $organization->display_name }}</h1>
                <p class="text-gray-600">Create a free account to start practicing</p>
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

            <form method="POST" action="{{ route('org.student.register.submit', $organization) }}" class="space-y-6">
                @csrf
                <div>
                    <label for="name" class="block text-sm font-semibold text-gray-700 mb-2">Name</label>
                    <input type="text" id="name" name="name" required autofocus
                           class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-400 focus:border-blue-400"
                           value="{{ old('name') }}">
                </div>
                <div>
                    <label for="email" class="block text-sm font-semibold text-gray-700 mb-2">Email</label>
                    <input type="email" id="email" name="email" required
                           class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-400 focus:border-blue-400"
                           value="{{ old('email') }}">
                </div>
                <div>
                    <label for="password" class="block text-sm font-semibold text-gray-700 mb-2">Password</label>
                    <input type="password" id="password" name="password" required
                           class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-400 focus:border-blue-400">
                    <p class="mt-1 text-sm text-gray-500">At least 8 characters</p>
                </div>
                <div>
                    <label for="password_confirmation" class="block text-sm font-semibold text-gray-700 mb-2">Confirm Password</label>
                    <input type="password" id="password_confirmation" name="password_confirmation" required
                           class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-400 focus:border-blue-400">
                </div>

                @if($organization->retain_voice_recordings)
                    <div>
                        <label for="age" class="block text-sm font-semibold text-gray-700 mb-2">Age</label>
                        <input type="number" id="age" name="age" min="5" max="120" required
                               class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-400 focus:border-blue-400"
                               value="{{ old('age') }}">
                    </div>
                    <div>
                        <label for="gender" class="block text-sm font-semibold text-gray-700 mb-2">Gender</label>
                        <select id="gender" name="gender" required
                                class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-400 focus:border-blue-400">
                            <option value="">Select...</option>
                            <option value="male" {{ old('gender') === 'male' ? 'selected' : '' }}>Male</option>
                            <option value="female" {{ old('gender') === 'female' ? 'selected' : '' }}>Female</option>
                        </select>
                    </div>
                    <div class="bg-blue-50 border border-blue-200 rounded-xl p-4">
                        <label class="flex items-start gap-3 cursor-pointer">
                            <input type="checkbox" name="voice_recording_consent" value="1" required
                                   class="mt-1 rounded border-gray-300 text-blue-600 focus:ring-blue-400"
                                   {{ old('voice_recording_consent') ? 'checked' : '' }}>
                            <span class="text-sm text-gray-700">{{ config('app.voice_waiver_text') }}</span>
                        </label>
                    </div>
                @endif

                <button type="submit" class="w-full px-6 py-3 bg-blue-600 text-white font-semibold rounded-xl hover:bg-blue-700 transition-colors">
                    Create Account
                </button>
            </form>

            <p class="mt-6 text-center text-gray-600">
                Already have an account?
                <a href="{{ route('org.student.login', $organization) }}" class="text-blue-600 hover:text-blue-700 font-medium">Sign in</a>
            </p>
        </div>
    </div>
</div>
@endsection
