@extends('layouts.app')

@section('title', config('app.name').' - Choose Your Course')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-blue-50 via-white to-purple-50 py-8">
    <div class="container mx-auto px-4 max-w-5xl">
        <!-- Welcome Section -->
        <div class="text-center mb-12">
            <h1 class="text-4xl md:text-5xl font-bold text-gray-800 mb-4 tracking-tight">
                @if(isset($currentOrganization))
                    {{ $currentOrganization->display_name }}
                @else
                    Welcome to {{ config('app.name') }}!
                @endif
            </h1>
            <p class="text-lg md:text-xl text-gray-600 font-medium">
                @if(isset($currentOrganization))
                    {{ __('student-portal.home.subtitle') }}
                @else
                    Practice English with fun activities
                @endif
            </p>
            @if(session('success'))
                <div class="mt-6 mx-auto max-w-lg bg-green-50 border border-green-200 text-green-800 p-4 rounded-xl">
                    {{ session('success') }}
                </div>
            @endif
        </div>

        @if(!isset($currentOrganization) && ($summerPracticePalOrg ?? null))
            <div class="mb-10 max-w-2xl mx-auto bg-white rounded-2xl border-2 border-purple-200 shadow-sm p-6 md:p-8 text-center">
                <h2 class="text-2xl font-bold text-gray-800 mb-2">{{ $summerPracticePalOrg->display_name }}</h2>
                <p class="text-gray-600 mb-6">
                    Summer English practice for families. Sign in or create an account to get started.
                </p>
                <div class="flex flex-col sm:flex-row justify-center gap-3">
                    <a href="{{ route('org.student.login', $summerPracticePalOrg) }}"
                       class="inline-flex items-center justify-center px-6 py-3 bg-purple-600 text-white font-semibold rounded-xl hover:bg-purple-700 transition-colors">
                        Sign in
                    </a>
                    <a href="{{ route('org.student.register', $summerPracticePalOrg) }}"
                       class="inline-flex items-center justify-center px-6 py-3 bg-white text-purple-700 font-semibold rounded-xl border-2 border-purple-200 hover:bg-purple-50 transition-colors">
                        Create account
                    </a>
                </div>
            </div>
        @endif

        <!-- Course Selection by Organization -->
        <div class="mb-8">
            <h2 class="text-2xl md:text-3xl font-bold text-gray-800 text-center mb-8">
                {{ __('student-portal.home.choose_course') }}
            </h2>
            @forelse($orgsWithCourses as $row)
                @php $org = $row['org']; $courses = $row['courses']; @endphp
                <section class="mb-12">
                    @unless(isset($currentOrganization))
                    <h3 class="text-xl font-bold text-gray-700 mb-4 flex items-center gap-2">
                        {{ $org->display_name }}
                    </h3>
                    @endunless
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        @foreach($courses as $course)
                            <a href="{{ route('org.student.course', [$org, $course]) }}" 
                               class="group relative bg-white rounded-2xl border-2 border-gray-200 shadow-sm hover:shadow-xl hover:border-blue-300 hover:-translate-y-1 transition-all duration-300 cursor-pointer block overflow-hidden">
                                @if($course->cover_image_path)
                                    <div class="h-48 overflow-hidden">
                                        <img src="{{ $course->cover_image_url }}" 
                                             alt="{{ $course->title }}" 
                                             class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                                    </div>
                                @endif
                                <div class="p-6">
                                    <h4 class="text-xl font-bold text-gray-800 mb-2 group-hover:text-blue-700 transition-colors duration-200">
                                        {{ $course->title }}
                                    </h4>
                                    @if($course->description)
                                        <p class="text-gray-600 text-sm mb-4 line-clamp-2">
                                            {{ $course->description }}
                                        </p>
                                    @endif
                                    <div class="flex items-center justify-between">
                                        <span class="text-sm text-gray-500">
                                            {{ trans_choice('student-portal.home.lessons_count', $course->lessons_count, ['count' => $course->lessons_count]) }}
                                        </span>
                                        <div class="opacity-0 group-hover:opacity-100 transition-opacity duration-200">
                                            <i class="fas fa-chevron-right text-blue-500"></i>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        @endforeach
                    </div>
                </section>
            @empty
                <div class="bg-white rounded-2xl border-2 border-gray-200 p-12 text-center shadow-sm">
                    <div class="text-6xl mb-4">📚</div>
                    <h3 class="text-2xl font-bold text-gray-800 mb-3">{{ __('student-portal.home.no_courses') }}</h3>
                    <p class="text-gray-600">{{ __('student-portal.home.check_back') }}</p>
                </div>
            @endforelse
        </div>
    </div>
</div>
@endsection
