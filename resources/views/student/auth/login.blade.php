@extends('layouts.app')

@section('title', 'Login — ' . $organization->display_name)

@section('content')
<div class="min-h-screen flex items-center justify-center py-12 px-4">
    <div class="w-full max-w-md">
        <div class="bg-white/90 backdrop-blur-sm rounded-2xl border border-gray-200/60 shadow-xl p-8">
            <div class="text-center mb-8">
                <h1 class="text-3xl font-bold text-gray-800 mb-2">{{ $organization->display_name }}</h1>
                <p class="text-gray-600">Sign in to access your courses</p>
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
                        {{ $organization->usesParentSignup() ? 'Email or phone' : 'Email' }}
                    </label>
                    <input type="text" id="email" name="email" required autofocus
                           class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-400 focus:border-blue-400"
                           value="{{ old('email') }}">
                </div>
                <div>
                    <label for="password" class="block text-sm font-semibold text-gray-700 mb-2">Password</label>
                    <input type="password" id="password" name="password" required
                           class="w-full px-4 py-3 border border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-400 focus:border-blue-400">
                </div>
                <div>
                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="checkbox" name="remember" value="1" {{ old('remember') ? 'checked' : '' }}
                               class="w-5 h-5 text-blue-600 border-gray-300 rounded">
                        <span class="text-gray-700 font-medium">Remember me</span>
                    </label>
                </div>
                <button type="submit" class="w-full px-6 py-3 bg-blue-600 text-white font-semibold rounded-xl hover:bg-blue-700 transition-colors">
                    Sign In
                </button>
            </form>

            @if($organization->allow_self_registration)
                <p class="mt-6 text-center text-gray-600">
                    Don't have an account?
                    <a href="{{ route('org.student.register', $organization) }}" class="text-blue-600 hover:text-blue-700 font-medium">Create one</a>
                </p>
            @endif
        </div>
    </div>
</div>
@endsection
