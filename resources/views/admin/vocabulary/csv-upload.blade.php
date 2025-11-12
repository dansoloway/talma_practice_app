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

        <form action="{{ route('admin.lessons.vocabulary.csv.process', $lesson) }}" method="POST" enctype="multipart/form-data" class="form" id="csv-upload-form">
            @csrf
            
            <div class="form-group">
                <label for="csv_file">Select CSV File *</label>
                <input type="file" id="csv_file" name="csv_file" accept=".csv,.txt" required class="form-control">
                <small>Choose a CSV or TXT file containing your vocabulary words</small>
            </div>

            <div class="form-group">
                <label>Import Mode *</label>
                <div class="radio-group">
                    <div class="radio-option">
                        <input type="radio" id="mode_add" name="import_mode" value="add" checked>
                        <label for="mode_add">
                            <strong>Add to existing vocabulary</strong>
                            <small>Keep current vocabulary and add new words from CSV</small>
                        </label>
                    </div>
                    <div class="radio-option">
                        <input type="radio" id="mode_replace" name="import_mode" value="replace">
                        <label for="mode_replace">
                            <strong>Replace all vocabulary</strong>
                            <small>Delete all current vocabulary and import only CSV words</small>
                        </label>
                    </div>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary" id="submit-btn">
                    <span id="submit-text">Upload and Import</span>
                    <span id="submit-spinner" style="display: none;">⏳ Processing...</span>
                </button>
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

<!-- Loading Modal -->
<div id="processing-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.7); z-index: 9999; align-items: center; justify-content: center;">
    <div style="background: white; padding: 2rem; border-radius: 8px; max-width: 500px; text-align: center; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);">
        <div style="font-size: 3rem; margin-bottom: 1rem;">⏳</div>
        <h2 style="margin-bottom: 1rem; color: #0024a7;">Processing CSV Import</h2>
        <p style="margin-bottom: 1.5rem; color: #666;">This may take a few minutes. Please don't close this page.</p>
        <div id="processing-status" style="margin-bottom: 1rem; color: #666; font-size: 0.9rem;">
            Uploading file...
        </div>
        <div style="width: 100%; height: 8px; background: #e5e7eb; border-radius: 4px; overflow: hidden;">
            <div id="processing-progress" style="height: 100%; background: #0024a7; width: 0%; transition: width 0.3s;"></div>
        </div>
        <p style="margin-top: 1.5rem; font-size: 0.875rem; color: #999;">
            Generating translations, images, and audio files...
        </p>
    </div>
</div>

@push('styles')
<style>
#processing-modal {
    display: flex;
}
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('csv-upload-form');
    const submitBtn = document.getElementById('submit-btn');
    const submitText = document.getElementById('submit-text');
    const submitSpinner = document.getElementById('submit-spinner');
    const processingModal = document.getElementById('processing-modal');
    const processingStatus = document.getElementById('processing-status');
    const processingProgress = document.getElementById('processing-progress');
    
    if (form) {
        form.addEventListener('submit', async function(e) {
            e.preventDefault(); // Prevent default form submission
            
            const fileInput = document.getElementById('csv_file');
            const importMode = document.querySelector('input[name="import_mode"]:checked').value;
            
            // Validate file is selected
            if (!fileInput.files || fileInput.files.length === 0) {
                alert('Please select a CSV file to upload.');
                return false;
            }
            
            // Show loading state on button
            submitBtn.disabled = true;
            submitText.style.display = 'none';
            submitSpinner.style.display = 'inline';
            
            // Show processing modal
            processingModal.style.display = 'flex';
            processingStatus.textContent = 'Uploading file...';
            processingProgress.style.width = '10%';
            
            // Create FormData
            const formData = new FormData();
            formData.append('csv_file', fileInput.files[0]);
            formData.append('import_mode', importMode);
            formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);
            
            // Simulate progress updates
            let progress = 10;
            const progressInterval = setInterval(function() {
                progress += Math.random() * 3;
                if (progress < 85) {
                    processingProgress.style.width = progress + '%';
                    
                    // Update status messages
                    if (progress < 20) {
                        processingStatus.textContent = 'Uploading file...';
                    } else if (progress < 40) {
                        processingStatus.textContent = 'Parsing CSV file...';
                    } else if (progress < 60) {
                        processingStatus.textContent = 'Translating words...';
                    } else if (progress < 80) {
                        processingStatus.textContent = 'Generating images...';
                    } else {
                        processingStatus.textContent = 'Generating audio files...';
                    }
                }
            }, 500);
            
            try {
                // Make AJAX request
                const response = await fetch(form.action, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });
                
                clearInterval(progressInterval);
                
                processingProgress.style.width = '100%';
                processingStatus.textContent = 'Processing complete!';
                
                // Parse response
                let data;
                try {
                    data = await response.json();
                } catch (e) {
                    throw new Error('Invalid response from server');
                }
                
                // Check if response is ok
                if (!response.ok) {
                    // Handle validation errors or other errors
                    if (data.errors) {
                        data.success = false;
                    } else {
                        throw new Error(data.message || 'Server returned an error: ' + response.status);
                    }
                }
                
                if (data.success) {
                    // Show success message
                    setTimeout(function() {
                        processingModal.style.display = 'none';
                        
                        // Show success alert
                        const alertDiv = document.createElement('div');
                        alertDiv.className = 'alert alert-success';
                        alertDiv.style.marginTop = '1rem';
                        alertDiv.innerHTML = '<strong>Success!</strong> ' + data.message;
                        form.parentElement.insertBefore(alertDiv, form);
                        
                        // Redirect after 2 seconds
                        setTimeout(function() {
                            window.location.href = data.redirect_url;
                        }, 2000);
                    }, 1000);
                } else {
                    // Show error
                    processingModal.style.display = 'none';
                    submitBtn.disabled = false;
                    submitText.style.display = 'inline';
                    submitSpinner.style.display = 'none';
                    
                    let errorMsg = data.message || 'An error occurred during import.';
                    if (data.errors) {
                        errorMsg += '<ul style="margin-top: 0.5rem;">';
                        Object.values(data.errors).forEach(function(errors) {
                            errors.forEach(function(error) {
                                errorMsg += '<li>' + error + '</li>';
                            });
                        });
                        errorMsg += '</ul>';
                    }
                    
                    const alertDiv = document.createElement('div');
                    alertDiv.className = 'alert alert-error';
                    alertDiv.style.marginTop = '1rem';
                    alertDiv.innerHTML = '<strong>Error!</strong> ' + errorMsg;
                    form.parentElement.insertBefore(alertDiv, form);
                }
            } catch (error) {
                clearInterval(progressInterval);
                processingModal.style.display = 'none';
                submitBtn.disabled = false;
                submitText.style.display = 'inline';
                submitSpinner.style.display = 'none';
                
                console.error('Error:', error);
                alert('An error occurred: ' + error.message);
            }
        });
    }
});
</script>
@endpush
@endsection
