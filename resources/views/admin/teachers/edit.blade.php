@extends('layouts.admin')

@section('title', 'Edit Teacher')

@section('content')
<div class="container">
    <div class="page-header">
        <h1 class="page-title">Edit Teacher</h1>
        <a href="{{ route('admin.teachers.index') }}" class="btn btn-secondary">Back to Teachers</a>
    </div>

    <form method="POST" action="{{ route('admin.teachers.update', $teacher) }}" class="form">
    @csrf
    @method('PUT')
    
    <div class="form-group">
        <label for="name">Name *</label>
        <input type="text" id="name" name="name" 
               value="{{ old('name', $teacher->name) }}" 
               required 
               class="form-control">
        @error('name')
            <span class="error">{{ $message }}</span>
        @enderror
    </div>
    
    <div class="form-group">
        <label for="email">Email *</label>
        <input type="email" id="email" name="email" 
               value="{{ old('email', $teacher->email) }}" 
               required 
               class="form-control">
        @error('email')
            <span class="error">{{ $message }}</span>
        @enderror
    </div>
    
    <div class="form-group">
        <label for="password">New Password (leave blank to keep current)</label>
        <input type="password" id="password" name="password" 
               minlength="8"
               class="form-control">
        @error('password')
            <span class="error">{{ $message }}</span>
        @enderror
    </div>
    
    <div class="form-group">
        <label for="password_confirmation">Confirm New Password</label>
        <input type="password" id="password_confirmation" name="password_confirmation" 
               minlength="8"
               class="form-control">
    </div>
    
    <div class="form-group">
        <label>
            <input type="checkbox" name="is_active" value="1" {{ old('is_active', $teacher->is_active) ? 'checked' : '' }}>
            Active
        </label>
    </div>
    
    <div class="form-actions">
        <button type="submit" class="btn btn-primary">Update Teacher</button>
        <a href="{{ route('admin.teachers.index') }}" class="btn btn-secondary">Cancel</a>
    </div>
    </form>
</div>
@endsection

