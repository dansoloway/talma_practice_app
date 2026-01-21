@extends('layouts.app')

@section('title', 'Admin Login - TALMA Practice Pal')

@section('content')
<div class="container">
    <div class="admin-login">
        <div class="login-card">
            <div class="login-header">
                <h1>Admin Login</h1>
                <p>Enter the admin password to access the admin dashboard</p>
            </div>

            @if(session('error'))
                <div class="error-message" style="background: #fee; color: #c33; padding: 1rem; border-radius: 8px; border: 2px solid #c33; margin-bottom: 1.5rem; font-weight: 600;">
                    <i class="fas fa-exclamation-circle"></i> {{ session('error') }}
                </div>
            @endif

            @if($errors->any())
                <div class="error-message" style="background: #fee; color: #c33; padding: 1rem; border-radius: 8px; border: 2px solid #c33; margin-bottom: 1.5rem;">
                    <strong><i class="fas fa-exclamation-circle"></i> Please fix the following errors:</strong>
                    <ul style="margin: 0.5rem 0 0 1.5rem; padding: 0;">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('admin.login') }}" class="login-form" id="login-form">
                @csrf
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" 
                           class="form-control @error('email') is-invalid @enderror" 
                           value="{{ old('email') }}" required autofocus>
                    @error('email')
                        <div class="error-text">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="password-input-wrapper">
                        <input type="password" id="password" name="password" 
                               class="form-control @error('password') is-invalid @enderror" required>
                        <button type="button" class="password-toggle" onclick="togglePassword()" aria-label="Show password">
                            <i class="fas fa-eye" id="password-toggle-icon"></i>
                        </button>
                    </div>
                    @error('password')
                        <div class="error-text">{{ $message }}</div>
                    @enderror
                </div>
                
                <button type="submit" class="btn btn-primary btn-large" id="login-submit-btn">
                    Access Admin Dashboard
                </button>
            </form>

            <div class="login-footer">
                <a href="{{ route('admin.password.forgot') }}" class="forgot-password-link">Forgot Password?</a>
                <a href="{{ route('student.index') }}" class="back-link">← Back to Student View</a>
            </div>
        </div>
    </div>
</div>

<script>
function togglePassword() {
    const passwordInput = document.getElementById('password');
    const toggleIcon = document.getElementById('password-toggle-icon');
    
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        toggleIcon.classList.remove('fa-eye');
        toggleIcon.classList.add('fa-eye-slash');
    } else {
        passwordInput.type = 'password';
        toggleIcon.classList.remove('fa-eye-slash');
        toggleIcon.classList.add('fa-eye');
    }
}

// Refresh CSRF token to prevent expiration
document.addEventListener('DOMContentLoaded', function() {
    const loginForm = document.getElementById('login-form');
    const csrfInput = loginForm?.querySelector('input[name="_token"]');
    const metaToken = document.querySelector('meta[name="csrf-token"]');
    
    // Function to refresh CSRF token
    function refreshCsrfToken() {
        return fetch('{{ route("admin.login.show") }}', {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'text/html',
            },
            credentials: 'same-origin',
            cache: 'no-store'
        })
        .then(response => {
            // Handle redirects (if user is already logged in)
            if (response.redirected || response.status === 302) {
                // If redirected, the current page token should still be valid
                return Promise.resolve(null);
            }
            if (!response.ok) {
                throw new Error('Failed to refresh token');
            }
            return response.text();
        })
        .then(html => {
            if (!html) {
                // Redirect case - use current token
                return;
            }
            
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            const newToken = doc.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ||
                           doc.querySelector('input[name="_token"]')?.getAttribute('value');
            
            if (newToken) {
                if (csrfInput) {
                    csrfInput.value = newToken;
                }
                if (metaToken) {
                    metaToken.setAttribute('content', newToken);
                }
            }
        })
        .catch(error => {
            console.error('Error refreshing CSRF token:', error);
        });
    }
    
    // Refresh token before form submission
    if (loginForm) {
        loginForm.addEventListener('submit', function(e) {
            const submitBtn = loginForm.querySelector('button[type="submit"]');
            const originalText = submitBtn ? submitBtn.textContent : '';
            
            // Prevent default submission temporarily
            e.preventDefault();
            
            // Update button state
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.textContent = 'Logging in...';
            }
            
            // Refresh token, then submit
            refreshCsrfToken().finally(() => {
                // Small delay to ensure token is updated in DOM
                setTimeout(() => {
                    // Re-enable button
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.textContent = originalText;
                    }
                    // Submit the form with the fresh token
                    loginForm.submit();
                }, 100);
            });
        });
    }
    
    // Refresh token periodically (every 5 minutes to stay fresh)
    setInterval(refreshCsrfToken, 5 * 60 * 1000);
    
    // Refresh token when user interacts with form fields
    if (loginForm) {
        ['email', 'password'].forEach(fieldName => {
            const field = loginForm.querySelector(`[name="${fieldName}"]`);
            if (field) {
                field.addEventListener('focus', refreshCsrfToken, { once: true });
            }
        });
    }
    
    // Refresh token immediately on page load to ensure it's fresh
    // This helps if the page was loaded a while ago
    refreshCsrfToken();
});
</script>

<style>
.password-input-wrapper {
    position: relative;
    display: flex;
    align-items: center;
}

.password-input-wrapper .form-control {
    padding-right: 45px;
}

.password-toggle {
    position: absolute;
    right: 10px;
    background: none;
    border: none;
    color: var(--color-text-light);
    cursor: pointer;
    padding: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: color 0.2s;
}

.password-toggle:hover {
    color: var(--color-primary);
}

.password-toggle:focus {
    outline: 2px solid var(--color-primary);
    outline-offset: 2px;
    border-radius: var(--radius-sm);
}

.forgot-password-link {
    color: var(--color-primary);
    text-decoration: none;
    font-size: 0.9rem;
    margin-bottom: 0.5rem;
    display: inline-block;
}

.forgot-password-link:hover {
    text-decoration: underline;
}

.login-footer {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.5rem;
    margin-top: 1.5rem;
}
</style>
@endsection
