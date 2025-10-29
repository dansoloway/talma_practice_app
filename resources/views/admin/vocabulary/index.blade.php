@extends('layouts.admin')

@section('title', 'Vocabulary for ' . $lesson->title)

@section('content')
<div class="container">
    <div class="page-header">
        <h1 class="page-title">Vocabulary for: {{ $lesson->title }}</h1>
        <div class="page-actions">
            <a href="{{ route('admin.lessons.vocabulary.create', $lesson) }}" class="btn btn-primary">Add Vocabulary</a>
            <a href="{{ route('admin.lessons.vocabulary.csv.upload', $lesson) }}" class="btn btn-secondary">Upload CSV</a>
            <a href="{{ route('admin.lessons.vocabulary.auto-images', $lesson) }}" class="btn btn-success">🔍 Auto-Find Images</a>
            <a href="{{ route('admin.lessons.show', $lesson) }}" class="btn">Back to Lesson</a>
        </div>
    </div>

    @if($vocabulary->count() > 0)
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>English Word</th>
                        <th>Hebrew Translation</th>
                        <th>Arabic Translation</th>
                        <th>Image</th>
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
</style>
@endpush
