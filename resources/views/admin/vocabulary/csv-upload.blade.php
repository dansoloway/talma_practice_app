@extends('layouts.admin')

@section('title', 'Upload Vocabulary CSV')

@section('content')
<div class="container">
    <div class="page-header">
        <h1 class="page-title">Upload Vocabulary CSV for: {{ $lesson->title }}</h1>
        <a href="{{ route('admin.lessons.vocabulary.index', $lesson) }}" class="btn">Back to Vocabulary</a>
    </div>

    <!-- Template Download Section - Moved Up -->
    <div class="template-section" style="background: #f0f9ff; border: 1px solid #bfdbfe; border-radius: 8px; padding: 1.5rem; margin-bottom: 2rem;">
        <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem;">
            <div>
                <h3 style="margin: 0 0 0.5rem 0; color: #0024a7; font-size: 1.125rem;">📥 Need a Template?</h3>
                <p style="margin: 0; color: #475569; font-size: 0.95rem;">Download a sample CSV file to see the correct format</p>
            </div>
            <a href="{{ route('admin.lessons.vocabulary.csv.template') }}" class="btn btn-secondary" style="white-space: nowrap;">Download Sample CSV</a>
        </div>
    </div>

    <!-- Main Upload Card -->
    <div class="upload-section" style="background: white; border: 1px solid #e2e8f0; border-radius: 8px; padding: 2rem; margin-bottom: 2rem;">
        <!-- Note at top -->
        <div style="background: #fff7ed; border-left: 4px solid #f59e0b; padding: 1rem; border-radius: 4px; margin-bottom: 2rem;">
            <p style="margin: 0; color: #92400e; font-weight: 500; font-size: 0.95rem;">
                <strong>Note:</strong> Only English words are accepted. Translations and other columns will be ignored.
            </p>
        </div>

        <form action="{{ route('admin.lessons.vocabulary.csv.process', $lesson) }}" method="POST" enctype="multipart/form-data" class="form" id="csv-upload-form">
            @csrf
            
            <!-- File Selection -->
            <div class="form-group" style="margin-bottom: 2rem;">
                <label for="csv_file" style="display: block; margin-bottom: 0.75rem; font-weight: 600; color: #1e293b; font-size: 1rem;">
                    Select CSV File <span style="color: #ef4444;">*</span>
                </label>
                <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                    <input type="file" id="csv_file" name="csv_file" accept=".csv,.txt" required 
                           class="form-control" 
                           style="padding: 0.75rem; border: 2px solid #e2e8f0; border-radius: 6px; font-size: 0.95rem; transition: border-color 0.2s;"
                           onchange="updateFileName(this)">
                    <small style="display: block; color: #64748b; font-size: 0.875rem; margin-top: 0.25rem;">
                        Choose a CSV or TXT file containing your vocabulary words (one word per line)
                    </small>
                </div>
            </div>

            <!-- Import Mode -->
            <div class="form-group" style="margin-bottom: 2rem;">
                <label style="display: block; margin-bottom: 0.75rem; font-weight: 600; color: #1e293b; font-size: 1rem;">
                    Import Mode <span style="color: #ef4444;">*</span>
                </label>
                <div class="radio-group" style="display: flex; flex-direction: column; gap: 1rem;">
                    <div class="radio-option" style="position: relative;">
                        <input type="radio" id="mode_add" name="import_mode" value="add" checked 
                               style="position: absolute; opacity: 0; width: 0; height: 0;">
                        <label for="mode_add" 
                               style="display: block; padding: 1rem 1rem 1rem 2.5rem; border: 2px solid #3b82f6; border-radius: 8px; background: #eff6ff; cursor: pointer; transition: all 0.2s;"
                               onmouseover="this.style.background='#dbeafe'" 
                               onmouseout="this.style.background='#eff6ff'">
                            <div style="position: absolute; left: 0.75rem; top: 50%; transform: translateY(-50%); width: 20px; height: 20px; border: 2px solid #3b82f6; border-radius: 50%; background: white; display: flex; align-items: center; justify-content: center;">
                                <div style="width: 10px; height: 10px; border-radius: 50%; background: #3b82f6; display: block;"></div>
                            </div>
                            <strong style="display: block; color: #1e40af; margin-bottom: 0.25rem;">Add to existing vocabulary</strong>
                            <small style="display: block; color: #64748b; font-size: 0.875rem;">Keep current vocabulary and add new words from CSV</small>
                        </label>
                    </div>
                    <div class="radio-option" style="position: relative;">
                        <input type="radio" id="mode_replace" name="import_mode" value="replace"
                               style="position: absolute; opacity: 0; width: 0; height: 0;">
                        <label for="mode_replace" 
                               style="display: block; padding: 1rem 1rem 1rem 2.5rem; border: 2px solid #cbd5e1; border-radius: 8px; background: #f8fafc; cursor: pointer; transition: all 0.2s;"
                               onmouseover="this.style.background='#f1f5f9'; this.style.borderColor='#94a3b8'" 
                               onmouseout="this.style.background='#f8fafc'; this.style.borderColor='#cbd5e1'">
                            <div style="position: absolute; left: 0.75rem; top: 50%; transform: translateY(-50%); width: 20px; height: 20px; border: 2px solid #cbd5e1; border-radius: 50%; background: white; display: flex; align-items: center; justify-content: center;">
                                <div style="width: 10px; height: 10px; border-radius: 50%; background: #cbd5e1; display: none;"></div>
                            </div>
                            <strong style="display: block; color: #475569; margin-bottom: 0.25rem;">Replace all vocabulary</strong>
                            <small style="display: block; color: #64748b; font-size: 0.875rem;">Delete all current vocabulary and import only CSV words</small>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="form-actions" style="display: flex; gap: 1rem; align-items: center; margin-top: 2.5rem; padding-top: 2rem; border-top: 1px solid #e2e8f0;">
                <button type="submit" class="btn btn-primary" id="submit-btn" 
                        style="flex: 1; padding: 1rem 2rem; font-size: 1.125rem; font-weight: 600; min-height: 56px; display: flex; align-items: center; justify-content: center; gap: 0.5rem;">
                    <span id="submit-text">📤 Upload and Import</span>
                    <span id="submit-spinner" style="display: none;">⏳ Processing...</span>
                </button>
                <a href="{{ route('admin.lessons.vocabulary.index', $lesson) }}" class="btn" 
                   style="padding: 1rem 2rem; font-size: 1rem; min-height: 56px; display: flex; align-items: center; justify-content: center;">
                    Cancel
                </a>
            </div>
        </form>
    </div>

    <!-- Info Section - Moved Down -->
    <div class="upload-info" style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 1.5rem;">
        <h3 style="margin: 0 0 1rem 0; color: #1e293b; font-size: 1.125rem;">📋 CSV Format Requirements</h3>
        
        <div style="margin-bottom: 1.5rem;">
            <h4 style="margin: 0 0 0.75rem 0; color: #475569; font-size: 1rem; font-weight: 600;">Example Format:</h4>
            <div style="background: white; border: 1px solid #e2e8f0; border-radius: 6px; padding: 1rem; font-family: monospace; font-size: 0.875rem;">
                <div style="padding: 0.25rem 0;">bus</div>
                <div style="padding: 0.25rem 0;">bun</div>
                <div style="padding: 0.25rem 0;">but</div>
                <div style="padding: 0.25rem 0;">bug</div>
            </div>
            <p style="margin-top: 0.75rem; font-size: 0.875rem; color: #64748b; font-style: italic;">
                No header row needed. Translations will be generated automatically after upload.
            </p>
        </div>
        
        <div class="csv-requirements">
            <h4 style="margin: 0 0 0.75rem 0; color: #475569; font-size: 1rem; font-weight: 600;">Requirements:</h4>
            <ul style="margin: 0; padding-left: 1.5rem; color: #475569; line-height: 1.75;">
                <li style="margin-bottom: 0.5rem;">Each row should contain <strong>only one English word</strong></li>
                <li style="margin-bottom: 0.5rem;">No header row needed - just list the words</li>
                <li style="margin-bottom: 0.5rem;">Additional columns will be ignored</li>
                <li style="margin-bottom: 0.5rem;">Only English letters, spaces, hyphens, and apostrophes are allowed</li>
                <li style="margin-bottom: 0.5rem;">File must be CSV or TXT format</li>
                <li style="margin-bottom: 0.5rem;">Maximum file size: 2MB</li>
            </ul>
        </div>
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
            Translating words and generating audio files...
        </p>
    </div>
</div>

@push('styles')
<style>
#processing-modal {
    display: flex;
}

/* Radio button styling */
input[type="radio"]:checked + label {
    border-color: #3b82f6 !important;
    background: #eff6ff !important;
}

input[type="radio"]:checked + label > div > div {
    display: flex !important;
    background: #3b82f6 !important;
}

input[type="radio"]:checked + label strong {
    color: #1e40af !important;
}

/* File input hover/focus states */
#csv_file:hover {
    border-color: #94a3b8;
}

#csv_file:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

/* Button hover states */
#submit-btn:hover:not(:disabled) {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0, 36, 167, 0.3);
}

#submit-btn:active:not(:disabled) {
    transform: translateY(0);
}
</style>
@endpush

@push('scripts')
<script>
function updateFileName(input) {
    // This function can be used to show the selected file name if needed
    if (input.files && input.files[0]) {
        console.log('Selected file:', input.files[0].name);
    }
}

// Update radio button visual state
document.addEventListener('DOMContentLoaded', function() {
    const radioInputs = document.querySelectorAll('input[name="import_mode"]');
    radioInputs.forEach(function(radio) {
        radio.addEventListener('change', function() {
            // Update all labels
            document.querySelectorAll('.radio-option label').forEach(function(label) {
                const radioId = label.getAttribute('for');
                const radioInput = document.getElementById(radioId);
                if (radioInput && radioInput.checked) {
                    label.style.borderColor = '#3b82f6';
                    label.style.background = '#eff6ff';
                    label.querySelector('strong').style.color = '#1e40af';
                    const dot = label.querySelector('div > div');
                    if (dot) {
                        dot.style.display = 'flex';
                        dot.style.background = '#3b82f6';
                    }
                } else {
                    label.style.borderColor = '#cbd5e1';
                    label.style.background = '#f8fafc';
                    label.querySelector('strong').style.color = '#475569';
                    const dot = label.querySelector('div > div');
                    if (dot) {
                        dot.style.display = 'none';
                    }
                }
            });
        });
    });
});

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
                    if (progress < 30) {
                        processingStatus.textContent = 'Uploading file...';
                    } else if (progress < 50) {
                        processingStatus.textContent = 'Parsing CSV file...';
                    } else if (progress < 50) {
                        processingStatus.textContent = 'Validating English words...';
                    } else if (progress < 70) {
                        processingStatus.textContent = 'Translating words...';
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
