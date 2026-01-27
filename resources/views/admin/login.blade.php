@extends('layouts.app')

@section('title', 'Admin Login - TALMA Practice Pal')

@section('content')
<div class="min-h-screen flex items-center justify-center py-12 px-4">
    <div class="w-full max-w-md">
        <div class="bg-white/90 backdrop-blur-sm rounded-2xl border border-gray-200/60 shadow-xl p-8">
            <div class="text-center mb-8">
                <h1 class="text-3xl font-bold text-gray-800 mb-2">Admin Login</h1>
                <p class="text-gray-600">Enter your credentials to access the admin dashboard</p>
            </div>

            @if(session('error'))
                <div class="bg-red-50/90 backdrop-blur-sm border border-red-200 text-red-800 p-4 rounded-xl shadow-sm mb-6">
                    <div class="flex items-center gap-3">
                        <i class="fas fa-exclamation-circle text-red-600"></i>
                        <p class="font-medium">{{ session('error') }}</p>
                    </div>
                </div>
            @endif

            @if($errors->any())
                <div class="bg-red-50/90 backdrop-blur-sm border border-red-200 text-red-800 p-4 rounded-xl shadow-sm mb-6">
                    <div class="flex items-start gap-3">
                        <i class="fas fa-exclamation-circle text-red-600 mt-0.5"></i>
                        <div>
                            <p class="font-semibold mb-2">Please fix the following errors:</p>
                            <ul class="list-disc list-inside space-y-1">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            @endif

            <form method="POST" action="{{ route('admin.login') }}" class="space-y-6" id="login-form">
                @csrf
                <div>
                    <label for="email" class="block text-sm font-semibold text-gray-700 mb-2">Email</label>
                    <input type="email" id="email" name="email" 
                           class="w-full px-4 py-3 border border-gray-300 rounded-xl bg-white text-gray-800 placeholder-gray-400 focus:ring-2 focus:ring-blue-400 focus:border-blue-400 transition-all duration-200 @error('email') border-red-300 focus:ring-red-400 focus:border-red-400 @enderror" 
                           value="{{ old('email') }}" required autofocus>
                    @error('email')
                        <div class="mt-2 text-sm text-red-600">{{ $message }}</div>
                    @enderror
                </div>
                
                <div>
                    <label for="password" class="block text-sm font-semibold text-gray-700 mb-2">Password</label>
                    <div class="relative">
                        <input type="password" id="password" name="password" 
                               class="w-full px-4 py-3 pr-12 border border-gray-300 rounded-xl bg-white text-gray-800 placeholder-gray-400 focus:ring-2 focus:ring-blue-400 focus:border-blue-400 transition-all duration-200 @error('password') border-red-300 focus:ring-red-400 focus:border-red-400 @enderror" required>
                        <button type="button" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 hover:text-gray-700 p-2 rounded-lg hover:bg-gray-100 transition-all duration-200" onclick="togglePassword()" aria-label="Show password">
                            <i class="fas fa-eye" id="password-toggle-icon"></i>
                        </button>
                    </div>
                    @error('password')
                        <div class="mt-2 text-sm text-red-600">{{ $message }}</div>
                    @enderror
                </div>
                
                <div>
                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="checkbox" name="remember" value="1" id="remember" {{ old('remember') ? 'checked' : '' }} class="w-5 h-5 text-blue-600 border-gray-300 rounded focus:ring-2 focus:ring-blue-400">
                        <span class="text-gray-700 font-medium">Remember me</span>
                    </label>
                    <p class="mt-2 text-sm text-gray-600">Stay logged in for 30 days</p>
                </div>
                
                <button type="submit" class="w-full px-6 py-3 bg-blue-600 text-white font-semibold rounded-xl hover:bg-blue-700 active:scale-95 transition-all duration-200 shadow-sm hover:shadow-md" id="login-submit-btn">
                    Access Admin Dashboard
                </button>
            </form>

            <div class="mt-6 text-center space-y-3">
                <a href="{{ route('admin.password.forgot') }}" class="block text-blue-600 hover:text-blue-700 font-medium text-sm transition-colors duration-200">Forgot Password?</a>
                <a href="{{ route('student.index') }}" class="block text-gray-600 hover:text-gray-800 font-medium text-sm transition-colors duration-200">← Back to Student View</a>
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

@endsection
