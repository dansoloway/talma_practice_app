@extends('layouts.app')

@section('title', __('student-portal.select_child.title').' — '.$organization->display_name)

@section('content')
<div class="min-h-screen flex items-center justify-center py-12 px-4">
    <div class="w-full max-w-lg">
        <div class="bg-white/90 backdrop-blur-sm rounded-2xl border border-gray-200/60 shadow-xl p-8">
            @if($organization->usesParentSignup())
                <x-signup-locale-switcher />
            @endif

            <h1 class="text-2xl font-bold text-gray-800 mb-2 text-center">{{ __('student-portal.select_child.title') }}</h1>
            <p class="text-gray-600 text-center mb-8">{{ __('student-portal.select_child.subtitle') }}</p>

            <form method="POST" action="{{ route('org.student.select-child.submit', $organization) }}" class="space-y-3">
                @csrf
                @foreach($students as $student)
                    <label class="flex items-center gap-4 p-4 border border-gray-200 rounded-xl cursor-pointer hover:border-blue-400 hover:bg-blue-50/50 transition-colors">
                        <input type="radio" name="student_id" value="{{ $student->id }}" required class="text-blue-600">
                        <span>
                            <span class="block font-semibold text-gray-800">{{ $student->display_name }}</span>
                            @if($student->grade)
                                <span class="text-sm text-gray-500">{{ \App\Support\SignupLocale::gradeLabel($student->grade) }}</span>
                            @endif
                        </span>
                    </label>
                @endforeach
                <button type="submit" class="w-full mt-4 px-6 py-3 bg-blue-600 text-white font-semibold rounded-xl hover:bg-blue-700">{{ __('student-portal.select_child.continue') }}</button>
            </form>
        </div>
    </div>
</div>
@endsection
