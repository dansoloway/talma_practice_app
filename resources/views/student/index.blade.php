@extends('layouts.app')

@section('title', 'TALMA Practice Pal - Choose Your Grade')

@section('content')
<div class="container">
    <div class="student-homepage">
        <div class="welcome-section">
            <h1 class="welcome-title">Welcome to TALMA Practice Pal!</h1>
            <p class="welcome-subtitle">Practice English with fun activities</p>
        </div>

        <div class="grade-selection">
            <h2>Choose Your Grade Level</h2>
            <div class="grade-grid">
                @forelse($gradeLevels as $grade)
                    <a href="{{ route('student.grade', $grade) }}" class="grade-card">
                        <div class="grade-number">{{ $grade }}</div>
                        <div class="grade-label">Grade {{ $grade }}</div>
                    </a>
                @empty
                    <div class="empty-state">
                        <h3>No lessons available yet</h3>
                        <p>Please check back later for new lessons!</p>
                    </div>
                @endforelse
            </div>
        </div>

    </div>
</div>
@endsection
