@extends('layouts.admin')

@section('title', 'TTS Generation Logs')

@section('content')
<div class="container">
    <div class="page-header">
        <h1 class="page-title">TTS Generation Logs</h1>
        <div class="page-actions">
            <a href="{{ route('admin.lessons.index') }}" class="btn">Back to Lessons</a>
            <a href="{{ route('admin.vocabulary.tts-logs', ['lines' => 100]) }}" class="btn btn-secondary">Last 100 lines</a>
            <a href="{{ route('admin.vocabulary.tts-logs', ['lines' => 500]) }}" class="btn btn-secondary">Last 500 lines</a>
            <button onclick="location.reload()" class="btn btn-primary">Refresh</button>
        </div>
    </div>

    <div class="log-container" style="background: #1e1e1e; color: #d4d4d4; padding: 1.5rem; border-radius: 8px; font-family: 'Courier New', monospace; font-size: 0.875rem; line-height: 1.6; max-height: 80vh; overflow-y: auto;">
        @if($logContent)
            <pre style="margin: 0; white-space: pre-wrap; word-wrap: break-word;">{{ $logContent }}</pre>
        @else
            <p style="color: #888;">No log content available.</p>
        @endif
    </div>

    <div style="margin-top: 1rem; padding: 1rem; background: #f0f9ff; border-radius: 4px; font-size: 0.875rem;">
        <p><strong>Showing last {{ $lines }} lines</strong></p>
        <p>Log file: <code>storage/logs/tts_generation.log</code></p>
        <p>This log shows TTS generation activity for vocabulary words. Refresh to see latest entries.</p>
    </div>
</div>

<style>
.log-container pre {
    color: #d4d4d4;
}

/* Highlight errors in red */
.log-container pre {
    color: #d4d4d4;
}

.log-container pre {
    color: inherit;
}

/* Auto-scroll to bottom on load */
.log-container {
    scroll-behavior: smooth;
}

.log-container pre::after {
    content: '';
    display: block;
    height: 1px;
}
</style>

<script>
// Auto-scroll to bottom on load
document.addEventListener('DOMContentLoaded', function() {
    const logContainer = document.querySelector('.log-container');
    if (logContainer) {
        logContainer.scrollTop = logContainer.scrollHeight;
    }
});
</script>
@endsection

