@extends('layouts.admin')

@section('title', 'Edit Grammar Concept')

@section('content')
<div class="container">
    <div class="page-header">
        <h1 class="page-title">Edit Grammar Concept</h1>
        <a href="{{ route('admin.grammar-concepts.index') }}" class="btn">Back to Grammar Sets</a>
    </div>

    <div class="card">
        <div class="card-body">
            <form action="{{ route('admin.grammar-concepts.update', $grammarConcept) }}" method="POST" class="form">
                @csrf
                @method('PUT')

                <div class="form-group">
                    <label for="title">Title *</label>
                    <input type="text" id="title" name="title" 
                           value="{{ old('title', $grammarConcept->title) }}" 
                           class="form-control" 
                           placeholder="e.g., Modals - Can/Could">
                    <small>A descriptive title for this grammar concept (auto-generated if left empty)</small>
                </div>

                <div class="form-group">
                    <label for="section">Section</label>
                    <input type="number" id="section" name="section" 
                           value="{{ old('section', $grammarConcept->section) }}" 
                           class="form-control">
                    <small>Optional section number</small>
                </div>

                <div class="form-group">
                    <label for="grammar_topic">Grammar Topic *</label>
                    <input type="text" id="grammar_topic" name="grammar_topic" 
                           value="{{ old('grammar_topic', $grammarConcept->grammar_topic) }}" 
                           class="form-control" required>
                    <small>e.g., "Modals and Semi-modals"</small>
                </div>

                <div class="form-group">
                    <label for="grammar_sub_topic">Grammar Sub Topic *</label>
                    <input type="text" id="grammar_sub_topic" name="grammar_sub_topic" 
                           value="{{ old('grammar_sub_topic', $grammarConcept->grammar_sub_topic) }}" 
                           class="form-control" required>
                    <small>e.g., "can", "could", "Comparative"</small>
                </div>

                @if($grammarConcept->lessons->count() > 0)
                    <div class="form-group">
                        <label>Associated Lessons</label>
                        <div style="background: var(--color-gray-50); padding: 1rem; border-radius: var(--radius-md);">
                            <p style="margin: 0 0 0.5rem 0; font-weight: 600;">This concept is used in {{ $grammarConcept->lessons->count() }} lesson(s):</p>
                            <ul style="margin: 0; padding-left: 1.5rem;">
                                @foreach($grammarConcept->lessons as $lesson)
                                    <li>
                                        <a href="{{ route('admin.lessons.show', $lesson) }}">{{ $lesson->title }}</a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                @endif

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Update Grammar Concept</button>
                    <a href="{{ route('admin.grammar-concepts.index') }}" class="btn">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
