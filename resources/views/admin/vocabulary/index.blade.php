@extends('layouts.admin')

@section('title', 'Vocabulary for ' . $lesson->title)

@section('content')
<div class="container">
    <div class="page-header">
        <h1 class="page-title">Vocabulary for: {{ $lesson->title }}</h1>
        <div class="page-actions">
            <button id="generate-images-btn" class="btn btn-primary" onclick="generateAllImages()">
                <span id="images-btn-text">🎨 Generate Images</span>
                <span id="images-btn-spinner" style="display: none;">⏳ Processing...</span>
            </button>
            <button id="generate-tts-btn" class="btn btn-primary" onclick="showTtsSettingsModal()">
                <span id="tts-btn-text">Generate/Recreate TTS</span>
                <span id="tts-btn-spinner" style="display: none;">⏳ Processing...</span>
            </button>
            
            <!-- Add Words Dropdown Menu -->
            <div class="dropdown" style="position: relative; display: inline-block;">
                <button type="button" class="btn btn-primary" id="add-words-toggle" onclick="toggleAddWordsMenu()" style="display: flex; align-items: center; gap: 0.5rem;">
                    <span>➕ Add Words</span>
                    <span id="dropdown-arrow" style="transition: transform 0.2s;">▼</span>
                </button>
                <div id="add-words-menu" class="dropdown-menu" style="display: none; position: absolute; top: 100%; left: 0; margin-top: 0.5rem; background: white; border: 1px solid #e2e8f0; border-radius: 8px; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); min-width: 220px; z-index: 1000; overflow: hidden;">
                    <button type="button" class="dropdown-item" onclick="scrollToBulkPaste()" style="display: block; width: 100%; padding: 0.75rem 1rem; text-align: left; background: none; border: none; color: #1e293b; cursor: pointer; transition: background-color 0.2s; border-bottom: 1px solid #f1f5f9;">
                        <div style="font-weight: 600; margin-bottom: 0.25rem;">📋 Paste Words <span style="font-size: 0.75rem; color: #2563eb; font-weight: 700;">(Recommended)</span></div>
                        <div style="font-size: 0.875rem; color: #64748b;">Paste multiple words, one per line</div>
                    </button>
                    <a href="{{ route('admin.lessons.vocabulary.csv.upload', $lesson) }}" class="dropdown-item" style="display: block; padding: 0.75rem 1rem; color: #1e293b; text-decoration: none; transition: background-color 0.2s; border-bottom: 1px solid #f1f5f9;">
                        <div style="font-weight: 600; margin-bottom: 0.25rem;">📤 Upload CSV</div>
                        <div style="font-size: 0.875rem; color: #64748b;">Import words from a CSV file</div>
                    </a>
                    <a href="{{ route('admin.lessons.vocabulary.create', $lesson) }}" class="dropdown-item" style="display: block; padding: 0.75rem 1rem; color: #1e293b; text-decoration: none; transition: background-color 0.2s;">
                        <div style="font-weight: 600; margin-bottom: 0.25rem;">➕ Add Single Word</div>
                        <div style="font-size: 0.875rem; color: #64748b;">Create one vocabulary word</div>
                    </a>
                </div>
            </div>
            
            <a href="{{ route('admin.lessons.manage', $lesson) }}" class="btn">Back to Lesson</a>
        </div>
    </div>

    @php $isEmptyVocabulary = $vocabulary->count() === 0; @endphp

    <!-- Bulk Paste Section -->
    <div class="bulk-paste-section" id="bulk-paste-section" style="background: white; border: {{ $isEmptyVocabulary ? '2px solid #3b82f6' : '1px solid var(--color-border, #ddd)' }}; border-radius: 8px; margin-bottom: 2rem; overflow: hidden; {{ $isEmptyVocabulary ? 'box-shadow: 0 4px 14px rgba(59, 130, 246, 0.12);' : '' }}">
        <div style="padding: 1rem 1.5rem; background: {{ $isEmptyVocabulary ? '#eff6ff' : '#f8fafc' }}; border-bottom: 1px solid #e2e8f0; cursor: pointer;" onclick="toggleBulkPaste()">
            <div style="display: flex; align-items: center; justify-content: space-between;">
                <h2 style="margin: 0; font-size: 1.25rem; color: var(--color-primary, #0024a7); display: flex; align-items: center; gap: 0.5rem;">
                    <span id="bulk-paste-arrow" style="transition: transform 0.2s; display: inline-block;">{{ $isEmptyVocabulary ? '▼' : '▶' }}</span>
                    <span>📋 Paste Words (One Per Line)</span>
                    @if($isEmptyVocabulary)
                        <span style="font-size: 0.75rem; font-weight: 700; color: #1d4ed8; background: #dbeafe; padding: 0.15rem 0.5rem; border-radius: 999px;">Recommended</span>
                    @endif
                </h2>
                <span style="color: #64748b; font-size: 0.875rem;">{{ $isEmptyVocabulary ? 'Start here' : 'Click to expand' }}</span>
            </div>
        </div>
        <div id="bulk-paste-content" style="display: {{ $isEmptyVocabulary ? 'block' : 'none' }}; padding: 1.5rem;">
            <form id="bulk-paste-form" action="{{ route('admin.lessons.vocabulary.bulk-store', $lesson) }}" method="POST">
                @csrf
                <div style="margin-bottom: 1rem;">
                    <label for="bulk-words" style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Paste words here (one word per line):</label>
                    <textarea 
                        id="bulk-words" 
                        name="words" 
                        rows="8" 
                        class="form-control" 
                        placeholder="cat&#10;dog&#10;bird&#10;fish"
                        style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 4px; font-family: monospace; font-size: 0.95rem; resize: vertical;"
                    ></textarea>
                    <small style="display: block; margin-top: 0.5rem; color: #666;">Each line will be created as a separate vocabulary word. Empty lines will be ignored.</small>
                    <p style="margin-top: 0.75rem; font-size: 0.875rem; color: #64748b;">
                        Prefer a file?
                        <a href="{{ route('admin.lessons.vocabulary.csv.upload', $lesson) }}" style="color: #2563eb; font-weight: 600; text-decoration: underline;">Upload CSV instead</a>
                    </p>
                </div>
                <div style="display: flex; gap: 1rem; align-items: center;">
                    <button type="submit" class="btn btn-primary" id="bulk-paste-btn">
                        <span id="bulk-paste-text">Add Words</span>
                        <span id="bulk-paste-spinner" style="display: none;">⏳ Processing...</span>
                    </button>
                    <span id="bulk-paste-status" style="display: none; color: #10b981; font-weight: 600;"></span>
                </div>
            </form>
        </div>
    </div>

    @if($vocabulary->count() > 0)
        <div id="images-status" style="display: none; margin-bottom: 1rem; padding: 1rem; background: #fef3c7; border-radius: 4px; border-left: 4px solid #f59e0b;">
            <div id="images-progress-text">Initializing image generation...</div>
            <div id="images-progress-bar" style="margin-top: 0.5rem; width: 100%; height: 8px; background: #e5e7eb; border-radius: 4px; overflow: hidden;">
                <div id="images-progress-fill" style="height: 100%; background: #f59e0b; width: 0%; transition: width 0.3s;"></div>
            </div>
            <div id="images-stats" style="margin-top: 0.5rem; font-size: 0.875rem; color: #6b7280;">
                <span id="images-processed">0</span> processed, <span id="images-remaining">0</span> remaining
            </div>
        </div>
        
        <!-- TTS Settings Modal -->
        <div id="tts-settings-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.7); z-index: 9999; align-items: center; justify-content: center;">
            <div style="background: white; padding: 2rem; border-radius: 8px; max-width: 600px; width: 90%; max-height: 90vh; overflow-y: auto; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);">
                <h2 style="margin-bottom: 1.5rem; color: #0024a7;">TTS Generation Settings</h2>
                
                <div style="margin-bottom: 1.5rem;">
                    <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Stability</label>
                    <input type="range" id="tts-stability" min="0" max="1" step="0.05" value="0.90" style="width: 100%;">
                    <div style="display: flex; justify-content: space-between; font-size: 0.875rem; color: #666; margin-top: 0.25rem;">
                        <span>More Natural (0.0)</span>
                        <span id="tts-stability-value">0.90</span>
                        <span>More Consistent (1.0)</span>
                    </div>
                </div>

                <div style="margin-bottom: 1.5rem;">
                    <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Similarity Boost</label>
                    <input type="range" id="tts-similarity" min="0" max="1" step="0.05" value="0.85" style="width: 100%;">
                    <div style="display: flex; justify-content: space-between; font-size: 0.875rem; color: #666; margin-top: 0.25rem;">
                        <span>Different Voice (0.0)</span>
                        <span id="tts-similarity-value">0.85</span>
                        <span>Original Voice (1.0)</span>
                    </div>
                </div>

                <div style="margin-bottom: 1.5rem;">
                    <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Style</label>
                    <input type="range" id="tts-style" min="0" max="1" step="0.05" value="0.0" style="width: 100%;">
                    <div style="display: flex; justify-content: space-between; font-size: 0.875rem; color: #666; margin-top: 0.25rem;">
                        <span>Neutral (0.0)</span>
                        <span id="tts-style-value">0.0</span>
                        <span>Expressive (1.0)</span>
                    </div>
                </div>

                <div style="margin-bottom: 1.5rem;">
                    <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Speed</label>
                    <input type="range" id="tts-speed" min="0.7" max="1.2" step="0.05" value="0.92" style="width: 100%;">
                    <div style="display: flex; justify-content: space-between; font-size: 0.875rem; color: #666; margin-top: 0.25rem;">
                        <span>Slow (0.7x)</span>
                        <span id="tts-speed-value">0.92</span>
                        <span>Fast (1.2x)</span>
                    </div>
                </div>

                <div style="margin-bottom: 1.5rem;">
                    <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                        <input type="checkbox" id="tts-speaker-boost" checked style="width: 18px; height: 18px;">
                        <span style="font-weight: 600;">Speaker Boost</span>
                        <span style="font-size: 0.875rem; color: #666; margin-left: 0.5rem;">(Enhanced clarity)</span>
                    </label>
                </div>

                <div style="margin-bottom: 1.5rem;">
                    <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Model</label>
                    <select id="tts-model" style="width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px;">
                        <option value="eleven_multilingual_v2" selected>Multilingual v2 (Recommended — English, best quality)</option>
                        <option value="eleven_flash_v2_5">Flash v2.5 (Fastest, batch processing)</option>
                        <option value="eleven_turbo_v2_5">Turbo v2.5 (Ultra-fast, low latency)</option>
                        <option value="eleven_flash_v2">Flash v2 (Fast, English only)</option>
                    </select>
                    <p style="font-size: 0.8rem; color: #666; margin-top: 0.35rem;">Deprecated models (monolingual v1) are no longer supported by ElevenLabs.</p>
                </div>

                <div style="display: flex; gap: 1rem; margin-top: 2rem;">
                    <button onclick="startTtsGeneration()" class="btn btn-primary" style="flex: 1;">
                        Start Generation
                    </button>
                    <button onclick="closeTtsSettingsModal(); currentSingleVocab = null;" class="btn" style="flex: 1;">
                        Cancel
                    </button>
                </div>
            </div>
        </div>

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
                                        <button 
                                            type="button" 
                                            class="btn-play-audio" 
                                            data-audio-url="{{ $item->word_audio_url }}"
                                            data-talma-audio-repeatable
                                            data-word="{{ $item->english_word }}"
                                            title="Play audio for '{{ $item->english_word }}'"
                                        >
                                            <span class="play-icon">▶</span>
                                            <span class="pause-icon" style="display: none;">⏸</span>
                                        </button>
                                    @else
                                        <span class="status inactive" title="Audio path set but file missing">
                                            <span class="status-icon">✗</span>
                                        </span>
                                    @endif
                                @else
                                    <span class="status inactive" title="No audio generated">
                                        <span class="status-icon">✗</span>
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
                                <a href="{{ route('admin.lessons.vocabulary.edit', [$lesson, $item]) }}" class="btn btn-sm btn-primary">Edit</a>
                                <button type="button" class="btn btn-sm btn-primary generate-image-btn" data-vocab-id="{{ $item->id }}" data-word="{{ $item->english_word }}" data-has-image="{{ $item->image_path ? '1' : '0' }}">
                                    {{ $item->image_path ? '🔄 Re-generate' : '🎨 Generate' }} Image
                                </button>
                                <button type="button" class="btn btn-sm btn-secondary generate-tts-btn" data-vocab-id="{{ $item->id }}" data-word="{{ $item->english_word }}" data-url="{{ route('admin.lessons.vocabulary.generate-single-tts', [$lesson, $item]) }}" title="Generate/Regenerate TTS audio">
                                    🔊 {{ $item->word_audio_path ? 'Regenerate' : 'Generate' }} TTS
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
            <p>Paste your word list above — one word per line. That's the fastest way to build a new lesson.</p>
            <div style="display: flex; gap: 0.75rem; justify-content: center; flex-wrap: wrap; margin-top: 1rem;">
                <button type="button" class="btn btn-primary" onclick="scrollToBulkPaste()">Paste Words</button>
                <a href="{{ route('admin.lessons.vocabulary.csv.upload', $lesson) }}" class="btn btn-secondary">Upload CSV</a>
                <a href="{{ route('admin.lessons.vocabulary.create', $lesson) }}" class="btn">Add Single Word</a>
            </div>
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

.btn-play-audio {
    padding: 0.25rem 0.5rem;
    font-size: 0.875rem;
    background: #3b82f6;
    color: white;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 0.25rem;
    transition: background-color 0.2s;
}

.btn-play-audio:hover {
    background: #2563eb;
}

.btn-play-audio:active {
    background: #1d4ed8;
    transform: scale(0.95);
}
</style>
@endpush

@push('scripts')
<script>
let imagesGenerationInProgress = false;
let imagesTotalItems = {{ $vocabulary->count() }};
let imagesProcessedItems = 0;

let ttsGenerationInProgress = false;
let ttsTotalItems = {{ $vocabulary->count() }};
let ttsProcessedItems = 0;
// Store current vocabulary item being regenerated (null = bulk regeneration)
let currentSingleVocab = null;

function generateAllImages() {
    if (imagesGenerationInProgress) {
        return;
    }

    if (!confirm('This will generate/recreate images for all {{ $vocabulary->count() }} vocabulary words. This may take several minutes. Continue?')) {
        return;
    }

    imagesGenerationInProgress = true;
    imagesProcessedItems = 0;
    
    const btn = document.getElementById('generate-images-btn');
    const btnText = document.getElementById('images-btn-text');
    const btnSpinner = document.getElementById('images-btn-spinner');
    const statusDiv = document.getElementById('images-status');
    const progressText = document.getElementById('images-progress-text');
    const progressFill = document.getElementById('images-progress-fill');
    const processedSpan = document.getElementById('images-processed');
    const remainingSpan = document.getElementById('images-remaining');

    btn.disabled = true;
    btnText.style.display = 'none';
    btnSpinner.style.display = 'inline';
    statusDiv.style.display = 'block';
    progressText.textContent = 'Starting image generation...';
    progressFill.style.width = '0%';

    function processNext() {
        fetch('{{ route("admin.lessons.vocabulary.generate-images", $lesson) }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                force: true  // Always recreate to regenerate existing images
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                imagesProcessedItems += data.processed || 0;
                const remaining = data.remaining || 0;
                const total = imagesTotalItems;
                const percent = Math.round((imagesProcessedItems / total) * 100);

                progressFill.style.width = percent + '%';
                processedSpan.textContent = imagesProcessedItems;
                remainingSpan.textContent = remaining;
                
                if (data.errors && data.errors.length > 0) {
                    progressText.textContent = `Processing... (Errors: ${data.errors.length})`;
                    console.error('Image Errors:', data.errors);
                } else {
                    progressText.textContent = `Processing... ${imagesProcessedItems} of ${total} completed`;
                }

                if (data.completed || remaining <= 0) {
                    // Finished
                    btn.disabled = false;
                    btnText.style.display = 'inline';
                    btnSpinner.style.display = 'none';
                    progressText.textContent = `✅ Completed! Generated images for ${imagesProcessedItems} vocabulary words.`;
                    progressFill.style.width = '100%';
                    progressFill.style.background = '#10b981';
                    imagesGenerationInProgress = false;
                    
                    // Reload page after 2 seconds to show updated images
                    setTimeout(() => {
                        window.location.reload();
                    }, 2000);
                } else {
                    // Continue processing
                    setTimeout(processNext, 1000); // 1 second delay for image generation
                }
            } else {
                throw new Error(data.message || 'Image generation failed');
            }
        })
        .catch(error => {
            console.error('Image Generation Error:', error);
            progressText.textContent = '❌ Error: ' + error.message;
            progressFill.style.background = '#ef4444';
            btn.disabled = false;
            btnText.style.display = 'inline';
            btnSpinner.style.display = 'none';
            imagesGenerationInProgress = false;
        });
    }

    processNext();
}

// TTS Settings localStorage functions
function loadTtsSettings() {
    const saved = localStorage.getItem('tts_settings');
    if (saved) {
        try {
            return JSON.parse(saved);
        } catch (e) {
            console.error('Error parsing saved TTS settings:', e);
        }
    }
    // Return default settings (optimized for single English words)
    return {
        stability: 0.90,
        similarity_boost: 0.85,
        style: 0.0,
        speed: 0.92,
        use_speaker_boost: true,
        model: 'eleven_multilingual_v2'
    };
}

function saveTtsSettings(settings) {
    try {
        localStorage.setItem('tts_settings', JSON.stringify(settings));
    } catch (e) {
        console.error('Error saving TTS settings:', e);
    }
}

function applyTtsSettings(settings) {
    // Apply settings to form inputs
    const stabilityInput = document.getElementById('tts-stability');
    const similarityInput = document.getElementById('tts-similarity');
    const styleInput = document.getElementById('tts-style');
    const speedInput = document.getElementById('tts-speed');
    const speakerBoostInput = document.getElementById('tts-speaker-boost');
    const modelInput = document.getElementById('tts-model');
    
    if (stabilityInput) {
        stabilityInput.value = settings.stability;
        document.getElementById('tts-stability-value').textContent = parseFloat(settings.stability).toFixed(2);
    }
    if (similarityInput) {
        similarityInput.value = settings.similarity_boost;
        document.getElementById('tts-similarity-value').textContent = parseFloat(settings.similarity_boost).toFixed(2);
    }
    if (styleInput) {
        styleInput.value = settings.style;
        document.getElementById('tts-style-value').textContent = parseFloat(settings.style).toFixed(2);
    }
    if (speedInput) {
        speedInput.value = settings.speed;
        document.getElementById('tts-speed-value').textContent = parseFloat(settings.speed).toFixed(2);
    }
    if (speakerBoostInput) {
        speakerBoostInput.checked = settings.use_speaker_boost;
    }
    if (modelInput) {
        modelInput.value = settings.model;
    }
}

function showTtsSettingsModal() {
    if (ttsGenerationInProgress) {
        return;
    }
    
    // Load saved settings and apply them
    const savedSettings = loadTtsSettings();
    applyTtsSettings(savedSettings);
    
    document.getElementById('tts-settings-modal').style.display = 'flex';
}

function closeTtsSettingsModal() {
    document.getElementById('tts-settings-modal').style.display = 'none';
    // Don't reset currentSingleVocab here - it will be reset after generation completes
    // This allows the generation function to access the vocab data after modal closes
}

// Update slider value displays and save settings on change
document.addEventListener('DOMContentLoaded', function() {
    const stabilitySlider = document.getElementById('tts-stability');
    const similaritySlider = document.getElementById('tts-similarity');
    const styleSlider = document.getElementById('tts-style');
    const speedSlider = document.getElementById('tts-speed');
    const speakerBoostInput = document.getElementById('tts-speaker-boost');
    const modelInput = document.getElementById('tts-model');

    function saveCurrentSettings() {
        const settings = {
            stability: parseFloat(stabilitySlider.value),
            similarity_boost: parseFloat(similaritySlider.value),
            style: parseFloat(styleSlider.value),
            speed: parseFloat(speedSlider.value),
            use_speaker_boost: speakerBoostInput.checked,
            model: modelInput.value
        };
        saveTtsSettings(settings);
    }

    if (stabilitySlider) {
        stabilitySlider.addEventListener('input', function() {
            document.getElementById('tts-stability-value').textContent = parseFloat(this.value).toFixed(2);
            saveCurrentSettings();
        });
    }
    if (similaritySlider) {
        similaritySlider.addEventListener('input', function() {
            document.getElementById('tts-similarity-value').textContent = parseFloat(this.value).toFixed(2);
            saveCurrentSettings();
        });
    }
    if (styleSlider) {
        styleSlider.addEventListener('input', function() {
            document.getElementById('tts-style-value').textContent = parseFloat(this.value).toFixed(2);
            saveCurrentSettings();
        });
    }
    if (speedSlider) {
        speedSlider.addEventListener('input', function() {
            document.getElementById('tts-speed-value').textContent = parseFloat(this.value).toFixed(2);
            saveCurrentSettings();
        });
    }
    if (speakerBoostInput) {
        speakerBoostInput.addEventListener('change', saveCurrentSettings);
    }
    if (modelInput) {
        modelInput.addEventListener('change', saveCurrentSettings);
    }
});

function startTtsGeneration() {
    if (ttsGenerationInProgress && !currentSingleVocab) {
        return;
    }

    // Get settings from modal
    const settings = {
        stability: parseFloat(document.getElementById('tts-stability').value),
        similarity_boost: parseFloat(document.getElementById('tts-similarity').value),
        style: parseFloat(document.getElementById('tts-style').value),
        speed: parseFloat(document.getElementById('tts-speed').value),
        use_speaker_boost: document.getElementById('tts-speaker-boost').checked,
        model: document.getElementById('tts-model').value
    };
    
    // Save settings to localStorage for next time
    saveTtsSettings(settings);

    // Check if this is for a single vocabulary item or bulk
    if (currentSingleVocab) {
        // Individual vocabulary regeneration - no confirmation needed
        // Close modal
        closeTtsSettingsModal();
        
        // Generate for single vocabulary item
        generateSingleVocabTts(settings);
        return;
    }

    // Bulk regeneration - no confirmation needed
    // Close modal
    closeTtsSettingsModal();

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
                force: true,  // Always recreate to regenerate existing audio
                reset: ttsProcessedItems === 0,  // Reset cache on first call
                settings: settings  // Pass TTS settings
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

function generateSingleVocabTts(settings) {
    if (!currentSingleVocab) return;
    
    const vocab = currentSingleVocab;
    
    // Disable button and show loading state
    vocab.button.disabled = true;
    vocab.button.textContent = '⏳ Generating...';
    
    console.log('Starting TTS generation for:', vocab.word, 'with settings:', settings);
    console.log('URL:', vocab.url);
    
    fetch(vocab.url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        },
        body: JSON.stringify({
            settings: settings
        })
    })
    .then(response => {
        console.log('Response status:', response.status, response.statusText);
        if (!response.ok) {
            return response.text().then(text => {
                console.error('Error response body:', text);
                throw new Error(`HTTP error! status: ${response.status} - ${text}`);
            });
        }
        return response.json();
    })
    .then(data => {
        console.log('TTS Generation Response:', data);
        if (data && data.success) {
            vocab.button.textContent = '✅ Generated';
            vocab.button.classList.remove('btn-secondary');
            vocab.button.classList.add('btn-success');
            
            // Reset current vocab
            currentSingleVocab = null;
            
            // Reload page immediately to show updated status and play button
            console.log('Reloading page...');
            window.location.reload();
        } else {
            console.error('Response indicates failure:', data);
            vocab.button.disabled = false;
            vocab.button.textContent = vocab.originalText;
            alert('Error: ' + (data?.message || 'Failed to generate TTS. Check console for details.'));
            currentSingleVocab = null;
        }
    })
    .catch(error => {
        console.error('TTS Generation Error:', error);
        console.error('Error stack:', error.stack);
        vocab.button.disabled = false;
        vocab.button.textContent = vocab.originalText;
        alert('Error: ' + error.message + '\n\nCheck browser console (F12) for more details.');
        currentSingleVocab = null;
    });
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

// TTS generation functionality
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.generate-tts-btn').forEach(button => {
        button.addEventListener('click', function() {
            // Store which vocabulary item this is for individual regeneration
            currentSingleVocab = {
                id: this.dataset.vocabId,
                word: this.dataset.word,
                url: this.dataset.url,
                button: this,
                originalText: this.textContent
            };
            
            // Show settings modal
            showTtsSettingsModal();
        });
    });
});

// Bulk paste form handling
document.addEventListener('DOMContentLoaded', function() {
    const bulkForm = document.getElementById('bulk-paste-form');
    const bulkBtn = document.getElementById('bulk-paste-btn');
    const bulkText = document.getElementById('bulk-paste-text');
    const bulkSpinner = document.getElementById('bulk-paste-spinner');
    const bulkStatus = document.getElementById('bulk-paste-status');
    const bulkTextarea = document.getElementById('bulk-words');

    if (bulkTextarea && bulkTextarea.closest('#bulk-paste-content')?.style.display === 'block') {
        bulkTextarea.focus();
    }

    if (bulkForm) {
        bulkForm.addEventListener('submit', function(e) {
            e.preventDefault();

            const words = bulkTextarea.value.trim();
            if (!words) {
                alert('Please enter at least one word.');
                return;
            }

            // Count words
            const wordCount = words.split('\n').filter(w => w.trim().length > 0).length;
            
            if (!confirm(`This will create ${wordCount} vocabulary word(s). Continue?`)) {
                return;
            }

            // Disable button and show loading
            bulkBtn.disabled = true;
            bulkText.style.display = 'none';
            bulkSpinner.style.display = 'inline';
            bulkStatus.style.display = 'none';

            // Submit form
            fetch(bulkForm.action, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: new URLSearchParams({
                    words: words
                })
            })
            .then(response => {
                if (response.redirected) {
                    // Follow redirect to see success message
                    window.location.href = response.url;
                } else {
                    return response.json();
                }
            })
            .then(data => {
                if (data) {
                    if (data.success) {
                        bulkStatus.textContent = '✓ ' + data.message;
                        bulkStatus.style.display = 'inline';
                        bulkTextarea.value = '';
                        setTimeout(() => {
                            window.location.reload();
                        }, 1500);
                    } else {
                        alert('Error: ' + (data.message || 'Failed to create vocabulary words'));
                        bulkBtn.disabled = false;
                        bulkText.style.display = 'inline';
                        bulkSpinner.style.display = 'none';
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred. Please try again.');
                bulkBtn.disabled = false;
                bulkText.style.display = 'inline';
                bulkSpinner.style.display = 'none';
            });
        });
    }
});

// Add Words Dropdown Menu Functions
function toggleAddWordsMenu() {
    const menu = document.getElementById('add-words-menu');
    const arrow = document.getElementById('dropdown-arrow');
    const isVisible = menu.style.display === 'block';
    
    if (isVisible) {
        menu.style.display = 'none';
        arrow.style.transform = 'rotate(0deg)';
    } else {
        menu.style.display = 'block';
        arrow.style.transform = 'rotate(180deg)';
    }
}

function toggleBulkPaste() {
    const content = document.getElementById('bulk-paste-content');
    const arrow = document.getElementById('bulk-paste-arrow');
    const isVisible = content.style.display === 'block';
    
    if (isVisible) {
        content.style.display = 'none';
        arrow.textContent = '▶';
    } else {
        content.style.display = 'block';
        arrow.textContent = '▼';
    }
}

function scrollToBulkPaste() {
    // Close the menu
    toggleAddWordsMenu();
    
    // Scroll to bulk paste section
    const bulkPasteSection = document.getElementById('bulk-paste-section');
    if (bulkPasteSection) {
        // Expand it if collapsed
        const content = document.getElementById('bulk-paste-content');
        if (content.style.display !== 'block') {
            toggleBulkPaste();
        }
        
        bulkPasteSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
        // Highlight it briefly
        bulkPasteSection.style.transition = 'background-color 0.3s';
        bulkPasteSection.style.backgroundColor = '#fef3c7';
        setTimeout(() => {
            bulkPasteSection.style.backgroundColor = 'white';
        }, 2000);
    }
}

// Close dropdown when clicking outside
document.addEventListener('click', function(event) {
    const dropdown = document.querySelector('.dropdown');
    const menu = document.getElementById('add-words-menu');
    const toggle = document.getElementById('add-words-toggle');
    
    if (dropdown && menu && toggle) {
        if (!dropdown.contains(event.target) && menu.style.display === 'block') {
            menu.style.display = 'none';
            document.getElementById('dropdown-arrow').style.transform = 'rotate(0deg)';
        }
    }
});
</script>
@endpush

@push('styles')
<style>
.dropdown-item:hover {
    background-color: #f8fafc !important;
}

.dropdown-item:active {
    background-color: #f1f5f9 !important;
}

.dropdown-item button:hover {
    background-color: #f8fafc !important;
}

.dropdown-item button:active {
    background-color: #f1f5f9 !important;
}
</style>
@endpush
