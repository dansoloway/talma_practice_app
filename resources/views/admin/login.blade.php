@extends('layouts.app')

@section('title', 'Admin Login - WeSpeak')

@section('content')
<div class="container">
    <div class="admin-login">
        <div class="login-card">
            <div class="login-header">
                <h1>Admin Login</h1>
                <p>Enter the admin password to access the admin dashboard</p>
            </div>

            @if(session('error'))
                <div class="error-message">
                    {{ session('error') }}
                </div>
            @endif

            <form method="POST" action="{{ route('admin.login') }}" class="login-form">
                @csrf
                <div class="form-group">
                    <label for="admin_password">Admin Password</label>
                    <input type="password" id="admin_password" name="admin_password" 
                           class="form-control" required autofocus>
                </div>
                
                <button type="submit" class="btn btn-primary btn-large">
                    Access Admin Dashboard
                </button>
            </form>

            <div class="login-footer">
                <a href="{{ route('student.index') }}" class="back-link">← Back to Student View</a>
            </div>
        </div>
    </div>
</div>
@endsection
