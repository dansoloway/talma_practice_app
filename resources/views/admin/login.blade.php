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
