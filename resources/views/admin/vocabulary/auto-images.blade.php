@extends('layouts.admin')

@section('title', 'Auto-Find Images for Vocabulary')

@section('content')
<div class="container">
    <div class="page-header">
        <h1>Auto-Find Images for Vocabulary</h1>
        <p class="subtitle">Automatically find suitable ESL images for vocabulary words</p>
        <div class="page-actions">
            <a href="{{ route('admin.lessons.vocabulary.index', $lesson) }}" class="btn">← Back to Vocabulary</a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="vocabulary-grid">
        @foreach($vocabulary as $item)
            <div class="vocabulary-card" data-vocab-id="{{ $item->id }}">
                <div class="vocab-header">
                    <h3>{{ $item->english_word }}</h3>
                    @if($item->image_path)
                        <div class="current-image">
                            <img src="{{ $item->image_url }}" alt="{{ $item->english_word }}" class="vocab-image">
                            <span class="image-status">✓ Has Image</span>
                        </div>
                    @else
                        <span class="image-status no-image">No Image</span>
                    @endif
                </div>
                
                <div class="image-actions">
                    <button class="btn btn-primary find-images-btn" data-word="{{ $item->english_word }}" data-vocab-id="{{ $item->id }}">
                        🔍 Find Images
                    </button>
                </div>

                <div class="image-suggestions" id="suggestions-{{ $item->id }}" style="display: none;">
                    <h4>Suggested Images:</h4>
                    <div class="suggestions-grid" id="grid-{{ $item->id }}">
                        <!-- Images will be loaded here -->
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>

<style>
.vocabulary-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 1.5rem;
    margin-top: 2rem;
}

.vocabulary-card {
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 1.5rem;
    background: white;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.vocab-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
}

.vocab-header h3 {
    margin: 0;
    color: var(--color-primary);
}

.current-image {
    text-align: center;
}

.current-image img {
    width: 60px;
    height: 60px;
    object-fit: cover;
    border-radius: 4px;
    border: 2px solid var(--color-primary);
}

.image-status {
    font-size: 0.875rem;
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    background: var(--color-success);
    color: white;
}

.image-status.no-image {
    background: var(--color-text-light);
    color: var(--color-text);
}

.image-actions {
    margin: 1rem 0;
}

.find-images-btn {
    width: 100%;
    padding: 0.75rem;
    font-size: 1rem;
}

.image-suggestions {
    margin-top: 1rem;
    padding-top: 1rem;
    border-top: 1px solid #eee;
}

.suggestions-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
    gap: 0.75rem;
    margin-top: 0.75rem;
}

.suggestion-item {
    position: relative;
    border: 2px solid transparent;
    border-radius: 6px;
    overflow: hidden;
    cursor: pointer;
    transition: all 0.2s;
}

.suggestion-item:hover {
    border-color: var(--color-primary);
    transform: scale(1.05);
}

.suggestion-item img {
    width: 100%;
    height: 80px;
    object-fit: cover;
}

.suggestion-info {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    background: rgba(0,0,0,0.7);
    color: white;
    padding: 0.25rem 0.5rem;
    font-size: 0.75rem;
}

.apply-btn {
    position: absolute;
    top: 0.25rem;
    right: 0.25rem;
    background: var(--color-primary);
    color: white;
    border: none;
    border-radius: 4px;
    padding: 0.25rem 0.5rem;
    font-size: 0.75rem;
    cursor: pointer;
}

.loading {
    text-align: center;
    padding: 2rem;
    color: var(--color-text-light);
}

.error {
    color: var(--color-danger);
    text-align: center;
    padding: 1rem;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const findButtons = document.querySelectorAll('.find-images-btn');
    
    findButtons.forEach(button => {
        button.addEventListener('click', function() {
            const word = this.dataset.word;
            const vocabId = this.dataset.vocabId;
            const suggestionsDiv = document.getElementById(`suggestions-${vocabId}`);
            const gridDiv = document.getElementById(`grid-${vocabId}`);
            
            // Show loading
            gridDiv.innerHTML = '<div class="loading">🔍 Searching for images...</div>';
            suggestionsDiv.style.display = 'block';
            
            // Fetch images
            fetch(`/admin/lessons/{{ $lesson->id }}/vocabulary/${vocabId}/find-images`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.images && data.images.length > 0) {
                    displayImages(data.images, gridDiv, vocabId);
                } else {
                    gridDiv.innerHTML = '<div class="error">No images found for this word</div>';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                gridDiv.innerHTML = '<div class="error">Error loading images</div>';
            });
        });
    });
    
    function displayImages(images, container, vocabId) {
        container.innerHTML = images.map((image, index) => `
            <div class="suggestion-item" onclick="applyImage('${image.url}', ${vocabId}, '${image.source}')">
                <img src="${image.thumb}" alt="${image.description}" loading="lazy">
                <div class="suggestion-info">
                    <div>${image.source}</div>
                    <div>${image.photographer}</div>
                </div>
                <button class="apply-btn">Use This</button>
            </div>
        `).join('');
    }
    
    window.applyImage = function(imageUrl, vocabId, source) {
        if (!confirm('Apply this image to the vocabulary word?')) return;
        
        fetch(`/admin/lessons/{{ $lesson->id }}/vocabulary/${vocabId}/apply-image`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                image_url: imageUrl,
                image_source: source
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Image applied successfully!');
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error applying image');
        });
    };
});
</script>
@endsection
