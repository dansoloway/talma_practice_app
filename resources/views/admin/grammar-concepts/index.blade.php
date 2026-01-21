@extends('layouts.admin')

@section('title', 'Grammar Concepts')

@section('content')
<div class="container">
    <div class="page-header">
        <h1 class="page-title">Grammar Concepts</h1>
        <div class="page-actions">
            <a href="{{ route('admin.grammar-concepts.csv.upload') }}" class="btn btn-primary">Upload CSV</a>
        </div>
    </div>

    @if(session('import_errors') && is_array(session('import_errors')))
        <div class="alert alert-error">
            <strong>Import Errors:</strong>
            <ul style="margin-top: 0.5rem;">
                @foreach(session('import_errors') as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if($grammarSets->count() > 0)
        <div class="card" style="margin-bottom: 2rem;">
            <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
                <h2 class="card-title">Grammar Sets</h2>
                <div style="display: flex; align-items: center; gap: 1rem;">
                    <label for="set-selector" style="font-weight: 600; margin: 0;">View Set:</label>
                    <select id="set-selector" class="form-control" style="min-width: 250px;">
                        <option value="">-- Select a Grammar Set --</option>
                        @foreach($grammarSets as $set)
                            <option value="{{ $set->id }}" data-concepts-count="{{ $set->grammarConcepts->count() }}">
                                {{ $set->title }} ({{ $set->grammarConcepts->count() }} concepts)
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        @foreach($grammarSets as $set)
            <div class="grammar-set-section" id="set-{{ $set->id }}" data-set-id="{{ $set->id }}" style="display: none;">
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">{{ $set->title }}</h2>
                        @if($set->description)
                            <p style="color: var(--color-text-muted); font-size: 0.875rem; margin: 0.5rem 0 0 0;">{{ $set->description }}</p>
                        @endif
                        <div style="font-size: 0.875rem; color: var(--color-text-muted); margin-top: 0.5rem;">
                            <strong>{{ $set->grammarConcepts->count() }}</strong> grammar concepts
                            @if($set->lessons->count() > 0)
                                | Used in <strong>{{ $set->lessons->count() }}</strong> lesson(s)
                            @endif
                            @if($set->source_file)
                                | Source: {{ $set->source_file }}
                            @endif
                        </div>
                    </div>
                    <div class="card-body">
                        @php
                            $setConcepts = $set->grammarConcepts->sortBy(function($concept) {
                                return [$concept->section ?? 999, $concept->grammar_topic, $concept->grammar_sub_topic];
                            });
                            $setGrouped = $setConcepts->groupBy(function ($concept) {
                                return ($concept->section ?? '') . '|' . $concept->grammar_topic;
                            });
                        @endphp
                        
                        @if($setGrouped->count() > 0)
                            @foreach($setGrouped as $groupKey => $groupConcepts)
                                @php
                                    [$section, $topic] = explode('|', $groupKey);
                                    $sectionLabel = $section ? "Section {$section}" : "No Section";
                                @endphp
                                <div class="grammar-group" style="margin-bottom: 2rem;">
                                    <h3 style="color: var(--color-primary); margin-bottom: 1rem; padding-bottom: 0.5rem; border-bottom: 2px solid var(--color-primary);">
                                        {{ $sectionLabel }}: {{ $topic }}
                                    </h3>
                                    <div class="concepts-list" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 1rem;">
                                        @foreach($groupConcepts as $concept)
                                            <div class="concept-card" style="background: white; border: 1px solid var(--color-border); border-radius: var(--radius-md); padding: 1rem; display: flex; justify-content: space-between; align-items: center;">
                                                <div style="flex: 1;">
                                                    <div style="font-weight: 600; color: var(--color-text);">{{ $concept->grammar_sub_topic }}</div>
                                                    @if($concept->section)
                                                        <div style="font-size: 0.75rem; color: var(--color-text-muted);">Section {{ $concept->section }}</div>
                                                    @endif
                                                </div>
                                                <div style="display: flex; gap: 0.5rem;">
                                                    <a href="{{ route('admin.grammar-concepts.edit', $concept) }}" class="btn btn-secondary" style="padding: 0.25rem 0.5rem; font-size: 0.875rem;">Edit</a>
                                                    <form action="{{ route('admin.grammar-concepts.destroy', $concept) }}" method="POST" style="margin: 0;" onsubmit="return confirm('Are you sure you want to delete this grammar concept?');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-danger" style="padding: 0.25rem 0.5rem; font-size: 0.875rem;">Delete</button>
                                                    </form>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        @else
                            <div class="empty-state" style="padding: 2rem; text-align: center;">
                                <p>No grammar concepts in this set.</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach

        <div id="no-set-selected" class="card" style="display: block;">
            <div class="card-body">
                <div class="empty-state" style="padding: 3rem; text-align: center;">
                    <h3>Select a Grammar Set</h3>
                    <p>Choose a grammar set from the dropdown above to view its concepts.</p>
                </div>
            </div>
        </div>
    @else
        <div class="empty-state">
            <h3>No Grammar Sets</h3>
            <p>You haven't imported any grammar sets yet. Upload a CSV file to get started.</p>
            <a href="{{ route('admin.grammar-concepts.csv.upload') }}" class="btn btn-primary">Upload CSV</a>
        </div>
    @endif
</div>
@endsection

@push('styles')
<style>
.empty-state {
    text-align: center;
    padding: 3rem 2rem;
    background: white;
    border-radius: var(--radius-lg);
    box-shadow: var(--shadow-md);
}

.empty-state h3 {
    color: var(--color-text);
    margin-bottom: 1rem;
}

.concept-card:hover {
    box-shadow: var(--shadow-sm);
    transform: translateY(-1px);
    transition: all 0.2s ease;
}
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const setSelector = document.getElementById('set-selector');
    const noSetSelected = document.getElementById('no-set-selected');
    
    // Function to show a specific set
    function showSet(setId) {
        // Hide all sets
        document.querySelectorAll('.grammar-set-section').forEach(section => {
            section.style.display = 'none';
        });
        
        // Show the selected set
        const selectedSection = document.getElementById('set-' + setId);
        if (selectedSection) {
            selectedSection.style.display = 'block';
            noSetSelected.style.display = 'none';
        } else {
            noSetSelected.style.display = 'block';
        }
        
        // Update URL hash without scrolling
        if (setId) {
            history.replaceState(null, null, '#set-' + setId);
        } else {
            history.replaceState(null, null, window.location.pathname);
        }
    }
    
    // Handle dropdown change
    if (setSelector) {
        setSelector.addEventListener('change', function() {
            const selectedSetId = this.value;
            showSet(selectedSetId);
        });
    }
    
    // Check URL hash on page load
    const hash = window.location.hash;
    if (hash && hash.startsWith('#set-')) {
        const setId = hash.replace('#set-', '');
        setSelector.value = setId;
        showSet(setId);
    }
});
</script>
@endpush
