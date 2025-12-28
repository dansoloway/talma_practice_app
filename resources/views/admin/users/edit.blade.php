@extends('layouts.admin')

@section('title', 'Edit User')

@section('content')
<div class="container">
    <div class="page-header">
        <h1 class="page-title">Edit User: {{ $user->name }}</h1>
        <a href="{{ route('admin.users.index') }}" class="btn">Cancel</a>
    </div>

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('admin.users.update', $user) }}" method="POST" class="form">
        @csrf
        @method('PUT')

        <div class="form-group">
            <label for="name">Name *</label>
            <input type="text" id="name" name="name" value="{{ old('name', $user->name) }}" required class="form-control">
        </div>

        <div class="form-group">
            <label for="email">Email *</label>
            <input type="email" id="email" name="email" value="{{ old('email', $user->email) }}" required class="form-control">
        </div>

        <div class="form-group">
            <label for="password">New Password</label>
            <input type="password" id="password" name="password" class="form-control" minlength="8">
            <small>Leave blank to keep current password. Minimum 8 characters if changing.</small>
        </div>

        <div class="form-group">
            <label for="password_confirmation">Confirm New Password</label>
            <input type="password" id="password_confirmation" name="password_confirmation" class="form-control">
        </div>

        <div class="form-group">
            <label for="role">Role *</label>
            <select id="role" name="role" required class="form-control">
                <option value="teacher" {{ old('role', $user->role) === 'teacher' ? 'selected' : '' }}>Teacher</option>
                <option value="admin" {{ old('role', $user->role) === 'admin' ? 'selected' : '' }}>Admin</option>
            </select>
        </div>

        <div class="form-group">
            <label class="checkbox-label">
                <input type="checkbox" name="is_active" value="1" {{ old('is_active', $user->is_active) ? 'checked' : '' }}>
                Active
            </label>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Update User</button>
            <a href="{{ route('admin.users.index') }}" class="btn">Cancel</a>
        </div>
    </form>
</div>
@endsection

