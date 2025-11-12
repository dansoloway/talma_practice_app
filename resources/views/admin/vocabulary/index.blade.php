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
                                    <img src="{{ $item->image_url }}" alt="{{ $item->english_word }}" class="vocab-thumbnail" data-image-url="{{ $item->image_url }}" data-word="{{ $item->english_word }}" style="width: 50px; height: 50px; object-fit: cover; border-radius: 4px; cursor: pointer;" onerror="this.style.display='none'; this.nextElementSibling.style.display='inline';">
                                    <span class="text-muted" style="display: none;">Image not found</span>
                                @else
                                    <span class="text-muted">No image</span>
                                @endif
                            </td>
                            <td>
                                @if($item->word_audio_path)
                                    @php
                                        // Use the hasAudioFile method from the model
                                        $audioExists = $item->hasAudioFile();
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
                                <button type="button" class="btn btn-sm btn-primary generate-image-btn" data-vocab-id="{{ $item->id }}" data-word="{{ $item->english_word }}" data-has-image="{{ $item->image_path ? '1' : '0' }}">
                                    {{ $item->image_path ? '🔄 Re-generate' : '🎨 Generate' }} Image
                                </button>
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

<!-- Image Modal -->
<div id="image-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.9); z-index: 10000; align-items: center; justify-content: center; cursor: pointer;">
    <div style="position: relative; max-width: 90%; max-height: 90%; text-align: center;">
        <button id="close-modal" style="position: absolute; top: -40px; right: 0; background: white; border: none; color: #333; font-size: 2rem; width: 40px; height: 40px; border-radius: 50%; cursor: pointer; line-height: 1;">×</button>
        <img id="modal-image" src="" alt="" style="max-width: 100%; max-height: 90vh; border-radius: 8px; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.5);">
        <p id="modal-word" style="color: white; margin-top: 1rem; font-size: 1.2rem; font-weight: 600;"></p>
    </div>
</div>

<!-- Image Generation Modal -->
<div id="generate-image-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.7); z-index: 9999; align-items: center; justify-content: center;">
    <div style="background: white; padding: 2rem; border-radius: 8px; max-width: 400px; text-align: center; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);">
        <div style="font-size: 3rem; margin-bottom: 1rem;">🎨</div>
        <h2 style="margin-bottom: 1rem; color: #0024a7;">Generating Image</h2>
        <p id="generate-status" style="margin-bottom: 1rem; color: #666;">Starting image generation...</p>
        <div style="width: 100%; height: 8px; background: #e5e7eb; border-radius: 4px; overflow: hidden;">
            <div id="generate-progress" style="height: 100%; background: #0024a7; width: 0%; transition: width 0.3s;"></div>
        </div>
    </div>
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

#image-modal {
    display: flex;
}

.vocab-thumbnail:hover {
    opacity: 0.8;
    transform: scale(1.05);
    transition: all 0.2s;
}

#generate-image-modal {
    display: flex;
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

// Image modal functionality
document.addEventListener('DOMContentLoaded', function() {
    const imageModal = document.getElementById('image-modal');
    const modalImage = document.getElementById('modal-image');
    const modalWord = document.getElementById('modal-word');
    const closeModal = document.getElementById('close-modal');
    const thumbnails = document.querySelectorAll('.vocab-thumbnail');
    
    // Open modal on thumbnail click
    thumbnails.forEach(function(thumbnail) {
        thumbnail.addEventListener('click', function() {
            modalImage.src = this.dataset.imageUrl;
            modalWord.textContent = this.dataset.word;
            imageModal.style.display = 'flex';
        });
    });
    
    // Close modal
    function closeImageModal() {
        imageModal.style.display = 'none';
    }
    
    closeModal.addEventListener('click', closeImageModal);
    imageModal.addEventListener('click', function(e) {
        if (e.target === imageModal) {
            closeImageModal();
        }
    });
    
    // Close on Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && imageModal.style.display === 'flex') {
            closeImageModal();
        }
    });
    
    // Image generation functionality
    const generateButtons = document.querySelectorAll('.generate-image-btn');
    const generateModal = document.getElementById('generate-image-modal');
    const generateStatus = document.getElementById('generate-status');
    const generateProgress = document.getElementById('generate-progress');
    
    generateButtons.forEach(function(btn) {
        btn.addEventListener('click', async function() {
            const vocabId = this.dataset.vocabId;
            const word = this.dataset.word;
            const hasImage = this.dataset.hasImage === '1';
            
            if (hasImage && !confirm(`Regenerate image for "${word}"? This will replace the current image.`)) {
                return;
            }
            
            // Show modal
            generateModal.style.display = 'flex';
            generateStatus.textContent = `Generating image for "${word}"...`;
            generateProgress.style.width = '20%';
            
            try {
                const response = await fetch(`/admin/lessons/{{ $lesson->id }}/vocabulary/${vocabId}/generate-image`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });
                
                generateProgress.style.width = '80%';
                generateStatus.textContent = 'Processing...';
                
                if (!response.ok) {
                    throw new Error('Server returned an error: ' + response.status);
                }
                
                const data = await response.json();
                
                generateProgress.style.width = '100%';
                generateStatus.textContent = 'Complete!';
                
                setTimeout(function() {
                    generateModal.style.display = 'none';
                    if (data.success) {
                        // Reload page to show new image
                        window.location.reload();
                    } else {
                        alert('Error: ' + (data.message || 'Failed to generate image'));
                    }
                }, 1000);
                
            } catch (error) {
                generateModal.style.display = 'none';
                console.error('Error:', error);
                alert('An error occurred: ' + error.message);
            }
        });
    });
});
</script>
@endpush
