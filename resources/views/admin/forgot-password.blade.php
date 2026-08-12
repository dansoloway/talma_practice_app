@extends('layouts.app')

@section('title', 'Forgot Password - '.config('app.name'))

@section('content')
<div class="container">
    <div class="admin-login">
        <div class="login-card">
            <div class="login-header">
                <h1>Reset Password</h1>
                <p>Enter your email address and we'll send you a link to reset your password</p>
            </div>

            @if(session('status'))
                <div class="success-message">
                    {{ session('status') }}
                </div>
            @endif

            @if($errors->any())
                <div class="error-message">
                    <ul>
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('admin.password.email') }}" class="login-form" id="forgot-password-form">
                @csrf
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" 
                           class="form-control" value="{{ old('email') }}" required autofocus>
                </div>
                
                <button type="submit" class="btn btn-primary btn-large" id="submit-btn">
                    Send Password Reset Link
                </button>
            </form>

            <div class="login-footer">
                <a href="{{ route('admin.dashboard') }}" class="back-link">← Back to Login</a>
            </div>
        </div>
    </div>
</div>

<style>
.success-message {
    background-color: #d4edda;
    color: #155724;
    padding: 1rem;
    border-radius: var(--radius-sm);
    margin-bottom: 1.5rem;
    border: 1px solid #c3e6cb;
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('forgot-password-form');
    const submitBtn = document.getElementById('submit-btn');
    let isSubmitting = false;

    if (form && submitBtn) {
        form.addEventListener('submit', function(e) {
            if (isSubmitting) {
                e.preventDefault();
                return false;
            }
            
            isSubmitting = true;
            submitBtn.disabled = true;
            submitBtn.textContent = 'Sending...';
            
            // Re-enable after 5 seconds as a safety measure
            setTimeout(function() {
                isSubmitting = false;
                submitBtn.disabled = false;
                submitBtn.textContent = 'Send Password Reset Link';
            }, 5000);
        });
    }
});
</script>
@endsection

