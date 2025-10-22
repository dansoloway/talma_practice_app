@extends('layouts.admin')

@section('title', 'Upload Vocabulary CSV')

@section('content')
<div class="container">
    <div class="page-header">
        <h1 class="page-title">Upload Vocabulary CSV for: {{ $lesson->title }}</h1>
        <a href="{{ route('admin.lessons.vocabulary.index', $lesson) }}" class="btn">Back to Vocabulary</a>
    </div>

    <div class="upload-section">
        <div class="upload-info">
            <h3>CSV Format</h3>
            <p>Your CSV file should have the following format:</p>
            <div class="csv-example">
                <table class="table">
                    <thead>
                        <tr>
                            <th>English Word</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>air pollution</td>
                        </tr>
                        <tr>
                            <td>water pollution</td>
                        </tr>
                        <tr>
                            <td>soil pollution</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <div class="csv-requirements">
                <h4>Requirements:</h4>
                <ul>
                    <li>Each row should contain one English word</li>
                    <li>File must be CSV or TXT format</li>
                    <li>Maximum file size: 2MB</li>
                    <li>Header row is optional (will be skipped if detected)</li>
                </ul>
            </div>
        </div>

        <form action="{{ route('admin.lessons.vocabulary.csv.process', $lesson) }}" method="POST" enctype="multipart/form-data" class="form">
            @csrf
            
            <div class="form-group">
                <label for="csv_file">Select CSV File *</label>
                <input type="file" id="csv_file" name="csv_file" accept=".csv,.txt" required class="form-control">
                <small>Choose a CSV or TXT file containing your vocabulary words</small>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Upload and Import</button>
                <a href="{{ route('admin.lessons.vocabulary.index', $lesson) }}" class="btn">Cancel</a>
            </div>
        </form>
    </div>

    <div class="download-section">
        <h3>Need a Template?</h3>
        <p>Download a sample CSV file to see the correct format:</p>
        <a href="{{ route('admin.lessons.vocabulary.csv.template') }}" class="btn btn-secondary">Download Sample CSV</a>
    </div>
</div>
@endsection
