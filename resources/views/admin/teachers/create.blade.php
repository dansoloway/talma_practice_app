@extends('layouts.admin')

@section('title', 'Add Teacher')

@section('content')
<div class="container">
    <div class="page-header">
        <h1 class="page-title">Add Teacher</h1>
        <a href="{{ route('admin.teachers.index') }}" class="btn btn-secondary">Back to Teachers</a>
    </div>

    <form method="POST" action="{{ route('admin.teachers.store') }}" class="form">
    @csrf
    
    <div class="form-group">
        <label for="name">Name *</label>
        <input type="text" id="name" name="name" 
               value="{{ old('name') }}" 
               required 
               class="form-control">
        @error('name')
            <span class="error">{{ $message }}</span>
        @enderror
    </div>
    
    <div class="form-group">
        <label for="email">Email *</label>
        <input type="email" id="email" name="email" 
               value="{{ old('email') }}" 
               required 
               class="form-control">
        @error('email')
            <span class="error">{{ $message }}</span>
        @enderror
    </div>
    
    <div class="form-group">
        <label for="password">Password *</label>
        <input type="password" id="password" name="password" 
               required 
               minlength="8"
               class="form-control">
        @error('password')
            <span class="error">{{ $message }}</span>
        @enderror
    </div>
    
    <div class="form-group">
        <label for="password_confirmation">Confirm Password *</label>
        <input type="password" id="password_confirmation" name="password_confirmation" 
               required 
               minlength="8"
               class="form-control">
    </div>
    
    <div class="form-group">
        <label>
            <input type="checkbox" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}>
            Active
        </label>
    </div>
    
    <div class="form-actions">
        <button type="submit" class="btn btn-primary">Create Teacher</button>
        <a href="{{ route('admin.teachers.index') }}" class="btn btn-secondary">Cancel</a>
    </div>
</form>
@endsection

