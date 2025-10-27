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
                    <li><strong>Template</strong> - The sentence template with <code>{{answer}}</code> placeholder</li>
                </ol>
                
                <h4>Optional Columns:</h4>
                <ol start="3">
                    <li><strong>TTS Voice</strong> - Voice for text-to-speech (default: "default")</li>
                    <li><strong>Option 1, Option 2, Option 3...</strong> - Answer choices for students</li>
                </ol>
            </div>

            <div class="csv-example">
                <h4>Example CSV Content:</h4>
                <pre class="code-block">Prompt Text,Template,TTS Voice,Option 1,Option 2,Option 3,Option 4
What is your favorite color?,My favorite color is {{answer}}.,default,red,blue,green,yellow
What do you like to eat?,I like to eat {{answer}}.,default,pizza,salad,soup,sandwich
Where do you live?,I live in {{answer}}.,default,New York,London,Tokyo,Paris</pre>
            </div>

            <div class="important-notes">
                <h4>Important Notes:</h4>
                <ul>
                    <li>The template <strong>must</strong> contain <code>{{answer}}</code> placeholder</li>
                    <li>You can include as many option columns as needed</li>
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
            <form action="{{ route('admin.lessons.prompts.import.store', $lesson) }}" method="POST" enctype="multipart/form-data">
                @csrf
                
                <div class="form-group">
                    <label for="csv_file">CSV File</label>
                    <input type="file" id="csv_file" name="csv_file" accept=".csv,.txt" required class="form-control">
                    <small class="form-text">Maximum file size: 2MB. Accepted formats: .csv, .txt</small>
                    @error('csv_file')
                        <div class="text-danger small">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Import Prompts</button>
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
