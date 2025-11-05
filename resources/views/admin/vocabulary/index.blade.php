@extends('layouts.admin')

@section('title', 'Vocabulary for ' . $lesson->title)

@section('content')
<div class="container">
    <div class="page-header">
        <h1 class="page-title">Vocabulary for: {{ $lesson->title }}</h1>
        <div class="page-actions">
            <button id="generate-tts-btn" class="btn btn-primary" onclick="generateAllTts()">
                <span id="tts-btn-text">Generate/Recreate TTS</span>
                <span id="tts-btn-spinner" style="display: none;">⏳ Processing...</span>
            </button>
            <a href="{{ route('admin.lessons.vocabulary.create', $lesson) }}" class="btn btn-primary">Add Vocabulary</a>
            <a href="{{ route('admin.lessons.vocabulary.csv.upload', $lesson) }}" class="btn btn-secondary">Upload CSV</a>
            <a href="{{ route('admin.lessons.manage', $lesson) }}" class="btn">Back to Lesson</a>
        </div>
    </div>

    @if($vocabulary->count() > 0)
        <div id="tts-status" style="display: none; margin-bottom: 1rem; padding: 1rem; background: #f0f9ff; border-radius: 4px; border-left: 4px solid #3b82f6;">
            <div id="tts-progress-text">Initializing TTS generation...</div>
            <div id="tts-progress-bar" style="margin-top: 0.5rem; width: 100%; height: 8px; background: #e5e7eb; border-radius: 4px; overflow: hidden;">
                <div id="tts-progress-fill" style="height: 100%; background: #3b82f6; width: 0%; transition: width 0.3s;"></div>
            </div>
            <div id="tts-stats" style="margin-top: 0.5rem; font-size: 0.875rem; color: #6b7280;">
                <span id="tts-processed">0</span> processed, <span id="tts-remaining">0</span> remaining
            </div>
        </div>
    @endif

    @if($vocabulary->count() > 0)
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>English Word</th>
                        <th>Hebrew Translation</th>
                        <th>Arabic Translation</th>
                        <th>Image</th>
                        <th>TTS Status</th>
                        <th>Sort Order</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($vocabulary as $item)
                        <tr>
                            <td><strong>{{ $item->english_word }}</strong></td>
                            <td>
                                @if($item->hebrew_translation)
                                    <span class="translation hebrew">{{ $item->hebrew_translation }}</span>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                @if($item->arabic_translation)
                                    <span class="translation arabic">{{ $item->arabic_translation }}</span>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                @if($item->image_path)
                                    <img src="{{ asset('storage/' . $item->image_path) }}" alt="{{ $item->english_word }}" style="width: 50px; height: 50px; object-fit: cover; border-radius: 4px;">
                                @else
                                    <span class="text-muted">No image</span>
                                @endif
                            </td>
                            <td>
                                @if($item->word_audio_path)
                                    @php
                                        $audioExists = \Illuminate\Support\Facades\Storage::disk('public')->exists($item->word_audio_path);
                                    @endphp
                                    @if($audioExists)
                                        <span class="status active" title="Audio file exists">
                                            <span class="status-icon">✓</span> Audio
                                        </span>
                                    @else
                                        <span class="status inactive" title="Audio path set but file missing">
                                            <span class="status-icon">⚠</span> Missing
                                        </span>
                                    @endif
                                @else
                                    <span class="status inactive" title="No audio generated">
                                        <span class="status-icon">✗</span> No Audio
                                    </span>
                                @endif
                            </td>
                            <td>{{ $item->sort_order }}</td>
                            <td>
                                <span class="status {{ $item->is_active ? 'active' : 'inactive' }}">
                                    {{ $item->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="actions">
                                <form action="{{ route('admin.lessons.vocabulary.update-image', [$lesson, $item]) }}" method="POST" enctype="multipart/form-data" class="inline-form">
                                    @csrf
                                    @method('PUT')
                                    <input type="file" name="image" accept="image/*" onchange="this.form.submit()" style="display: none;" id="image-{{ $item->id }}">
                                    <label for="image-{{ $item->id }}" class="btn btn-sm">Upload Image</label>
                                </form>
                                @if($item->image_path)
                                    <form action="{{ route('admin.lessons.vocabulary.remove-image', [$lesson, $item]) }}" method="POST" class="inline-form">
                                        @csrf
                                        @method('PUT')
                                        <button type="submit" class="btn btn-sm btn-secondary" onclick="return confirm('Remove image?')">Remove Image</button>
                                    </form>
                                @endif
                                <form action="{{ route('admin.lessons.vocabulary.destroy', [$lesson, $item]) }}" method="POST" class="inline-form">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Delete this vocabulary item?')">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="empty-state">
            <h3>No vocabulary yet</h3>
            <p>This lesson doesn't have any vocabulary items yet. Add vocabulary words to help students learn.</p>
            <a href="{{ route('admin.lessons.vocabulary.create', $lesson) }}" class="btn btn-primary">Add First Vocabulary Item</a>
        </div>
    @endif
</div>
@endsection

@push('styles')
<style>
.translation {
    font-size: 0.9rem;
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    display: inline-block;
}

.translation.hebrew {
    background-color: #e8f4fd;
    color: #1e40af;
    border: 1px solid #bfdbfe;
}

.translation.arabic {
    background-color: #f0fdf4;
    color: #166534;
    border: 1px solid #bbf7d0;
}

.text-muted {
    color: #6b7280;
    font-style: italic;
}

.status-icon {
    margin-right: 0.25rem;
    font-weight: bold;
}
</style>
@endpush

@push('scripts')
<script>
let ttsGenerationInProgress = false;
let ttsTotalItems = {{ $vocabulary->count() }};
let ttsProcessedItems = 0;

function generateAllTts() {
    if (ttsGenerationInProgress) {
        return;
    }

    if (!confirm('This will generate/recreate TTS audio for all {{ $vocabulary->count() }} vocabulary words. This may take several minutes. Continue?')) {
        return;
    }

    ttsGenerationInProgress = true;
    ttsProcessedItems = 0;
    
    const btn = document.getElementById('generate-tts-btn');
    const btnText = document.getElementById('tts-btn-text');
    const btnSpinner = document.getElementById('tts-btn-spinner');
    const statusDiv = document.getElementById('tts-status');
    const progressText = document.getElementById('tts-progress-text');
    const progressFill = document.getElementById('tts-progress-fill');
    const processedSpan = document.getElementById('tts-processed');
    const remainingSpan = document.getElementById('tts-remaining');

    btn.disabled = true;
    btnText.style.display = 'none';
    btnSpinner.style.display = 'inline';
    statusDiv.style.display = 'block';
    progressText.textContent = 'Starting TTS generation...';
    progressFill.style.width = '0%';

    function processNext() {
        fetch('{{ route("admin.lessons.vocabulary.generate-tts", $lesson) }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                force: true  // Always recreate to regenerate existing audio
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                ttsProcessedItems += data.processed || 0;
                const remaining = data.remaining || 0;
                const total = ttsTotalItems;
                const percent = Math.round((ttsProcessedItems / total) * 100);

                progressFill.style.width = percent + '%';
                processedSpan.textContent = ttsProcessedItems;
                remainingSpan.textContent = remaining;
                
                if (data.errors && data.errors.length > 0) {
                    progressText.textContent = `Processing... (Errors: ${data.errors.length})`;
                    console.error('TTS Errors:', data.errors);
                } else {
                    progressText.textContent = `Processing... ${ttsProcessedItems} of ${total} completed`;
                }

                if (data.completed || remaining <= 0) {
                    // Finished
                    btn.disabled = false;
                    btnText.style.display = 'inline';
                    btnSpinner.style.display = 'none';
                    progressText.textContent = `✅ Completed! Generated TTS for ${ttsProcessedItems} vocabulary words.`;
                    progressFill.style.width = '100%';
                    progressFill.style.background = '#10b981';
                    ttsGenerationInProgress = false;
                    
                    // Reload page after 2 seconds to show updated status
                    setTimeout(() => {
                        window.location.reload();
                    }, 2000);
                } else {
                    // Continue processing
                    setTimeout(processNext, 500);
                }
            } else {
                throw new Error(data.message || 'TTS generation failed');
            }
        })
        .catch(error => {
            console.error('TTS Generation Error:', error);
            progressText.textContent = '❌ Error: ' + error.message;
            progressFill.style.background = '#ef4444';
            btn.disabled = false;
            btnText.style.display = 'inline';
            btnSpinner.style.display = 'none';
            ttsGenerationInProgress = false;
        });
    }

    processNext();
}
</script>
@endpush
