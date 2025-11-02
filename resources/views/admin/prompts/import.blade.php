@extends('layouts.admin')

@section('title', 'Import Prompts - ' . $lesson->title)

@section('content')
<div class="container">
    <div class="page-header">
        <div>
            <a href="{{ route('admin.lessons.manage', $lesson) }}" class="back-link">&larr; Back to Lesson</a>
            <h1 class="page-title">Import Prompts</h1>
            <p class="page-subtitle">{{ $lesson->title }}</p>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">CSV Format Instructions</h2>
        </div>
        <div class="card-body">
            <p>Upload a CSV file with the following columns:</p>
            
            <div class="format-example">
                <h4>Required Columns:</h4>
                <ol>
                    <li><strong>Prompt Text</strong> - The question or instruction shown to students</li>
                    <li><strong>Template</strong> - The sentence template with <code>{}</code> placeholder</li>
                    <li><strong>Option 1, Option 2, Option 3...</strong> - Answer choices for students</li>
                </ol>
                <h4>Optional Column:</h4>
                <ul>
                    <li><strong>Correct</strong> - A number indicating the correct option's position (e.g. 1 for Option 1). If provided as the last column, it will be used to set the correct answer.</li>
                </ul>
            </div>

            <div class="csv-example">
                <h4>Example CSV Content:</h4>
                <pre class="code-block">Prompt Text,Template,Option 1,Option 2,Option 3,Option 4,Correct
What rolled the farthest?,The {} rolled the farthest,ball,cube,cylinder,sphere,4
What object is the softest?,The {} is the softest,cotton,sponge,fabric,pillow,2
What object is the hardest?,The {} is the hardest,rock,metal,wood,glass,2</pre>
            </div>

            <div class="important-notes">
                <h4>Important Notes:</h4>
                <ul>
                    <li>The template <strong>must</strong> contain <code>{}</code> as placeholder for the answer</li>
                    <li>You can include as many option columns as needed</li>
                    <li>If you include a <strong>Correct</strong> column at the end, it must be a number between 1 and the number of options in that row</li>
                    <li>If the <strong>Correct</strong> column is omitted, no correct answer will be pre-set</li>
                    <li>Empty rows will be skipped</li>
                    <li>The first row is treated as headers and will be ignored</li>
                    <li>Prompts will be automatically assigned to the lesson</li>
                </ul>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Upload CSV File</h2>
        </div>
        <div class="card-body">
            <form action="{{ route('admin.lessons.prompts.preview', $lesson) }}" method="POST" enctype="multipart/form-data">
                @csrf
                
                <div class="form-group">
                    <label for="csv_file">CSV File</label>
                    <input type="file" id="csv_file" name="csv_file" accept=".csv,.txt" required class="form-control">
                    <small class="form-text">Maximum file size: 2MB. Accepted formats: .csv, .txt</small>
                    @error('csv_file')
                        <div class="text-danger small">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group">
                    <label>Import Mode</label>
                    <div class="radio-group">
                        <div class="radio-option">
                            <input type="radio" id="prompt_mode_add" name="import_mode" value="add" checked>
                            <label for="prompt_mode_add">
                                <strong>Add to existing prompts</strong>
                                <small>Keep current prompts and add new ones from CSV</small>
                            </label>
                        </div>
                        <div class="radio-option">
                            <input type="radio" id="prompt_mode_replace" name="import_mode" value="replace">
                            <label for="prompt_mode_replace">
                                <strong>Replace all prompts</strong>
                                <small>Delete all current prompts and import only CSV prompts</small>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Preview Import</button>
                    <a href="{{ route('admin.lessons.manage', $lesson) }}" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>

    @if(session('import_errors'))
        <div class="card">
            <div class="card-header">
                <h2 class="card-title text-danger">Import Errors</h2>
            </div>
            <div class="card-body">
                <p>The following errors occurred during import:</p>
                <ul class="error-list">
                    @foreach(session('import_errors') as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Need a Template?</h2>
        </div>
        <div class="card-body">
            <p>Download a sample CSV file to see the correct format and get started quickly:</p>
            <a href="{{ route('admin.lessons.prompts.csv.template') }}" class="btn btn-secondary">
                <i class="fas fa-download"></i> Download Sample CSV
            </a>
            <p class="mt-3"><small>The template includes example prompts with the correct {} placeholder format and multiple answer options.</small></p>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.format-example {
    background: var(--color-gray-50);
    padding: var(--spacing-xl);
    border-radius: var(--radius-lg);
    margin: var(--spacing-lg) 0;
}

.format-example h4 {
    color: var(--color-primary);
    margin-bottom: var(--spacing-md);
}

.format-example ol {
    margin-bottom: var(--spacing-lg);
}

.format-example li {
    margin-bottom: var(--spacing-sm);
}

.csv-example {
    margin: var(--spacing-lg) 0;
}

.code-block {
    background: var(--color-gray-900);
    color: var(--color-white);
    padding: var(--spacing-lg);
    border-radius: var(--radius-md);
    overflow-x: auto;
    font-family: 'Courier New', monospace;
    font-size: 0.875rem;
    line-height: 1.4;
}

.important-notes {
    background: var(--color-warning-bg);
    border: 1px solid var(--color-warning-light);
    padding: var(--spacing-xl);
    border-radius: var(--radius-lg);
    margin: var(--spacing-lg) 0;
}

.important-notes h4 {
    color: var(--color-warning-dark);
    margin-bottom: var(--spacing-md);
}

.important-notes ul {
    margin: 0;
}

.important-notes li {
    margin-bottom: var(--spacing-sm);
}

.error-list {
    background: var(--color-danger-bg);
    border: 1px solid var(--color-danger-light);
    padding: var(--spacing-lg);
    border-radius: var(--radius-md);
    margin: 0;
}

.error-list li {
    margin-bottom: var(--spacing-sm);
    color: var(--color-danger-dark);
}

.page-subtitle {
    color: var(--color-gray-600);
    font-size: 1rem;
    margin: 0;
}

code {
    background: var(--color-gray-100);
    color: var(--color-danger);
    padding: 0.2em 0.4em;
    border-radius: var(--radius-sm);
    font-family: 'Courier New', monospace;
    font-size: 0.875em;
}
</style>
@endpush
