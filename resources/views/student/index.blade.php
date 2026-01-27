@extends('layouts.app')

@section('title', 'TALMA Practice Pal - Choose Your Grade')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-blue-50 via-white to-purple-50 py-8">
    <div class="container mx-auto px-4 max-w-5xl">
        <!-- Welcome Section -->
        <div class="text-center mb-12">
            <h1 class="text-4xl md:text-5xl font-bold text-gray-800 mb-4 tracking-tight">
                Welcome to TALMA Practice Pal!
            </h1>
            <p class="text-lg md:text-xl text-gray-600 font-medium">
                Practice English with fun activities
            </p>
        </div>

        <!-- Grade Selection -->
        <div class="mb-8">
            <h2 class="text-2xl md:text-3xl font-bold text-gray-800 text-center mb-8">
                Choose Your Grade Level
            </h2>
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 md:gap-6">
                @forelse($gradeLevels as $grade)
                    <a href="{{ route('student.grade', $grade)" 
                       class="group relative bg-white rounded-2xl border-2 border-gray-200 p-6 md:p-8 shadow-sm hover:shadow-xl hover:border-blue-300 hover:-translate-y-1 transition-all duration-300 cursor-pointer block text-center">
                        <div class="text-5xl md:text-6xl font-bold text-blue-600 mb-3 group-hover:text-blue-700 transition-colors duration-200">
                            {{ $grade }}
                        </div>
                        <div class="text-base md:text-lg font-semibold text-gray-700 group-hover:text-gray-900 transition-colors duration-200">
                            Grade {{ $grade }}
                        </div>
                        <div class="absolute top-4 right-4 opacity-0 group-hover:opacity-100 transition-opacity duration-200">
                            <i class="fas fa-chevron-right text-blue-500"></i>
                        </div>
                    </a>
                @empty
                    <div class="col-span-full">
                        <div class="bg-white rounded-2xl border-2 border-gray-200 p-12 text-center shadow-sm">
                            <div class="text-6xl mb-4">📚</div>
                            <h3 class="text-2xl font-bold text-gray-800 mb-3">No lessons available yet</h3>
                            <p class="text-gray-600">Please check back later for new lessons!</p>
                        </div>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
