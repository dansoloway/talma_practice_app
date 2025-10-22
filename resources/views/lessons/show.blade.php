@extends('layouts.app')

@section('title', $lesson->title)

@section('content')
<div class="container">
    <div class="lesson-header">
        <a href="{{ route('lessons.index') }}" class="back-link">&larr; Back to Lessons</a>
        <h1 class="page-title">{{ $lesson->title }}</h1>
        
        @if($lesson->grade_level || $lesson->session_number || $lesson->session_title)
            <div class="lesson-metadata">
                @if($lesson->grade_level)
                    <span class="grade-level">Grade {{ $lesson->grade_level }}</span>
                @endif
                @if($lesson->session_number)
                    <span class="session-number">Session {{ $lesson->session_number }}</span>
                @endif
                @if($lesson->session_title)
                    <span class="session-title">{{ $lesson->session_title }}</span>
                @endif
            </div>
        @endif
        
        @if($lesson->instructions)
            <div class="lesson-instructions">
                <h3>Instructions</h3>
                <p>{{ $lesson->instructions }}</p>
            </div>
        @endif

        @if($lesson->vocabulary && $lesson->vocabulary->count() > 0)
            <div class="vocabulary-section">
                <h3>Vocabulary for this lesson</h3>
                <div class="vocabulary-grid">
                    @foreach($lesson->vocabulary as $vocab)
                        <div class="vocabulary-item">
                            @if($vocab->image_path)
                                <img src="{{ asset('storage/' . $vocab->image_path) }}" alt="{{ $vocab->english_word }}" class="vocab-image">
                            @endif
                            <div class="vocab-word">{{ $vocab->english_word }}</div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>

    <div id="lesson-app" class="lesson-container">
        <div class="progress-bar">
            <div class="progress-fill" id="progress-fill"></div>
        </div>

        <!-- Part Instructions Screen -->
        <div class="part-instructions" id="part-instructions">
            <div class="part-header">
                <h2 id="part-title"></h2>
                <p id="part-description"></p>
            </div>
            <button id="start-part-btn" class="btn btn-primary btn-large">Start This Part</button>
        </div>

        <div class="prompt-step hidden" id="prompt-step">
            <div class="prompt-header">
                <h2 class="prompt-text" id="prompt-text"></h2>
                <button id="prompt-audio-btn" class="prompt-audio-btn hidden" title="Listen to prompt">
                    ▶ Play Question
                </button>
            </div>
            <div class="option-grid" id="option-grid"></div>
        </div>

        <div class="model-step hidden" id="model-step">
            <div class="model-sentence">
                <p id="model-text" class="model-text"></p>
                <button id="play-btn" class="btn btn-large btn-primary">▶ Play Model</button>
            </div>

            <div class="practice-section">
                <h3>Your Turn</h3>
                <div class="recorder">
                    <button id="record-btn" class="btn btn-secondary">🎤 Record</button>
                    <button id="stop-btn" class="btn btn-secondary hidden">⏹ Stop</button>
                    <button id="playback-btn" class="btn btn-secondary hidden">▶ Play Your Recording</button>
                    <span id="recording-status" class="recording-status"></span>
                </div>
                <button id="next-btn" class="btn btn-primary">Next →</button>
            </div>
        </div>

        <div class="completion-step hidden" id="completion-step">
            <h2>🎉 Part Complete!</h2>
            <p>Great job practicing your sentences.</p>
            <button id="next-part-btn" class="btn btn-primary">Next Part</button>
            <a href="{{ route('lessons.index') }}" class="btn">Back to Lessons</a>
        </div>

        <div class="lesson-complete hidden" id="lesson-complete">
            <h2>🎉 Lesson Complete!</h2>
            <p>Excellent work! You've completed all parts of this lesson.</p>
            <a href="{{ route('lessons.index') }}" class="btn btn-primary">Back to Lessons</a>
        </div>
    </div>
</div>

<audio id="prompt-audio" preload="auto"></audio>
<audio id="model-audio" preload="auto"></audio>
<audio id="playback-audio"></audio>

<script>
const lessonData = @json($lesson);
const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
</script>
@endsection

@push('scripts')
<script src="{{ asset('js/lesson.js') }}"></script>
@endpush

