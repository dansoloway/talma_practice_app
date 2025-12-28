@extends('layouts.admin')

@section('title', 'Create User')

@section('content')
<div class="container">
    <div class="page-header">
        <h1 class="page-title">Create User</h1>
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

    <form action="{{ route('admin.users.store') }}" method="POST" class="form">
        @csrf

        <div class="form-group">
            <label for="name">Name *</label>
            <input type="text" id="name" name="name" value="{{ old('name') }}" required class="form-control">
        </div>

        <div class="form-group">
            <label for="email">Email *</label>
            <input type="email" id="email" name="email" value="{{ old('email') }}" required class="form-control">
        </div>

        <div class="form-group">
            <label for="password">Password *</label>
            <input type="password" id="password" name="password" required class="form-control" minlength="8">
            <small>Minimum 8 characters</small>
        </div>

        <div class="form-group">
            <label for="password_confirmation">Confirm Password *</label>
            <input type="password" id="password_confirmation" name="password_confirmation" required class="form-control">
        </div>

        <div class="form-group">
            <label for="role">Role *</label>
            <select id="role" name="role" required class="form-control">
                <option value="teacher" {{ old('role') === 'teacher' ? 'selected' : '' }}>Teacher</option>
                <option value="admin" {{ old('role') === 'admin' ? 'selected' : '' }}>Admin</option>
            </select>
        </div>

        <div class="form-group">
            <label class="checkbox-label">
                <input type="checkbox" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}>
                Active
            </label>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Create User</button>
            <a href="{{ route('admin.users.index') }}" class="btn">Cancel</a>
        </div>
    </form>
</div>
@endsection

