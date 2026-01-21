@extends('layouts.admin')

@section('title', 'Upload Grammar Concepts CSV')

@section('content')
<div class="container">
    <div class="page-header">
        <h1 class="page-title">Upload Grammar Concepts CSV</h1>
        <a href="{{ route('admin.grammar-concepts.index') }}" class="btn">Back to Grammar Concepts</a>
    </div>

    <div class="upload-section">
        <div class="upload-info">
            <h3>CSV Format</h3>
            <p>Your CSV file should have the following format:</p>
            <div class="csv-example">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Section</th>
                            <th>Grammar Topic</th>
                            <th>Grammar Sub Topic</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>1</td>
                            <td>Modals and Semi-modals</td>
                            <td>can</td>
                        </tr>
                        <tr>
                            <td>1</td>
                            <td>Modals and Semi-modals</td>
                            <td>could</td>
                        </tr>
                        <tr>
                            <td>2</td>
                            <td>Adjectives</td>
                            <td>Comparative</td>
                        </tr>
                        <tr>
                            <td>2</td>
                            <td>Adjectives</td>
                            <td>Superlative</td>
                        </tr>
                    </tbody>
                </table>
                <p style="margin-top: 0.5rem; font-size: 0.875rem; color: #666; font-style: italic;">
                    The first row should be headers: Section, Grammar Topic, Grammar Sub Topic
                </p>
            </div>
            
            <div class="csv-requirements">
                <h4>Requirements:</h4>
                <ul>
                    <li><strong>Section</strong> - Optional numeric value (can be empty)</li>
                    <li><strong>Grammar Topic</strong> - Required (e.g., "Modals and Semi-modals")</li>
                    <li><strong>Grammar Sub Topic</strong> - Required (e.g., "can", "could", "Comparative")</li>
                    <li>File must be CSV or TXT format</li>
                    <li>Maximum file size: 2MB</li>
                </ul>
            </div>
        </div>

        <form action="{{ route('admin.grammar-concepts.csv.process') }}" method="POST" enctype="multipart/form-data" class="form" id="csv-upload-form">
            @csrf
            
            <div class="form-group">
                <label for="set_title">Grammar Set Title *</label>
                <input type="text" id="set_title" name="set_title" 
                       value="{{ old('set_title', 'Grammar Set ' . date('Y-m-d')) }}" 
                       required class="form-control">
                <small>Give this set a name so you can associate it with lessons later (e.g., "Basic Grammar Set", "Advanced Modals")</small>
            </div>

            <div class="form-group">
                <label for="csv_file">Select CSV File *</label>
                <input type="file" id="csv_file" name="csv_file" accept=".csv,.txt" required class="form-control">
                <small>Choose a CSV or TXT file containing your grammar concepts</small>
            </div>

            <div class="form-group">
                <label>Import Mode *</label>
                <div class="radio-group">
                    <div class="radio-option">
                        <input type="radio" id="mode_add" name="import_mode" value="add" checked>
                        <label for="mode_add">
                            <strong>Add to existing concepts</strong>
                            <small>Keep current concepts and add new ones from CSV</small>
                        </label>
                    </div>
                    <div class="radio-option">
                        <input type="radio" id="mode_replace" name="import_mode" value="replace">
                        <label for="mode_replace">
                            <strong>Replace all concepts</strong>
                            <small>Delete all current concepts and import only CSV concepts</small>
                        </label>
                    </div>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary" id="submit-btn">
                    <span id="submit-text">Upload and Import</span>
                    <span id="submit-spinner" style="display: none;">⏳ Processing...</span>
                </button>
                <a href="{{ route('admin.grammar-concepts.index') }}" class="btn">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection

@push('styles')
<style>
.upload-section {
    background: white;
    border-radius: var(--radius-lg);
    padding: 2rem;
    box-shadow: var(--shadow-md);
    margin-bottom: 2rem;
}

.upload-info {
    margin-bottom: 2rem;
}

.upload-info h3 {
    color: var(--color-primary);
    margin-bottom: 1rem;
}

.csv-example {
    background: var(--color-gray-50);
    border-radius: var(--radius-md);
    padding: 1rem;
    margin: 1rem 0;
    overflow-x: auto;
}

.csv-example table {
    width: 100%;
    border-collapse: collapse;
}

.csv-example th,
.csv-example td {
    padding: 0.5rem;
    text-align: left;
    border: 1px solid var(--color-border);
}

.csv-example th {
    background: var(--color-primary);
    color: white;
    font-weight: 600;
}

.csv-requirements {
    margin-top: 1.5rem;
}

.csv-requirements h4 {
    color: var(--color-text);
    margin-bottom: 0.5rem;
}

.csv-requirements ul {
    list-style: disc;
    margin-left: 1.5rem;
    color: var(--color-text-muted);
}

.csv-requirements li {
    margin-bottom: 0.5rem;
}

.radio-group {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.radio-option {
    display: flex;
    align-items: flex-start;
    gap: 0.5rem;
}

.radio-option input[type="radio"] {
    margin-top: 0.25rem;
}

.radio-option label {
    flex: 1;
    cursor: pointer;
}

.radio-option label strong {
    display: block;
    color: var(--color-text);
    margin-bottom: 0.25rem;
}

.radio-option label small {
    display: block;
    color: var(--color-text-muted);
    font-size: 0.875rem;
}
</style>
@endpush

@push('scripts')
<script>
document.getElementById('csv-upload-form').addEventListener('submit', function(e) {
    const submitBtn = document.getElementById('submit-btn');
    const submitText = document.getElementById('submit-text');
    const submitSpinner = document.getElementById('submit-spinner');
    
    submitBtn.disabled = true;
    submitText.style.display = 'none';
    submitSpinner.style.display = 'inline';
});
</script>
@endpush
