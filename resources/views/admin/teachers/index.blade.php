@extends('layouts.admin')

@section('title', 'Teacher Management')

@section('content')
<div class="container">
    <div class="page-header">
        <h1 class="page-title">Teacher Management</h1>
        <a href="{{ route('admin.teachers.create') }}" class="btn btn-primary">
            <i class="fas fa-plus"></i> Add Teacher
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    <!-- Filters -->
    <div class="filters-section">
        <form method="GET" action="{{ route('admin.teachers.index') }}" class="filters-form">
            <div class="filter-group">
                <label for="search">Search</label>
                <input type="text" id="search" name="search" 
                       value="{{ request('search') }}" 
                       placeholder="Name or email..."
                       class="form-control">
            </div>
            
            <div class="filter-group">
                <label for="status">Status</label>
                <select id="status" name="status" class="form-control">
                    <option value="">All</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>
            
            <div class="filter-actions">
                <button type="submit" class="btn btn-primary btn-sm">Filter</button>
                <a href="{{ route('admin.teachers.index') }}" class="btn btn-secondary btn-sm">Clear</a>
            </div>
        </form>
    </div>

    @if($teachers->isEmpty())
        <div class="empty-state">
            <p>No teachers found.</p>
        </div>
    @else
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($teachers as $teacher)
                        <tr>
                            <td><strong>{{ $teacher->name }}</strong></td>
                            <td>{{ $teacher->email }}</td>
                            <td>
                                <span class="badge {{ $teacher->is_active ? 'badge-success' : 'badge-danger' }}">
                                    {{ $teacher->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td>{{ $teacher->created_at->format('M d, Y') }}</td>
                            <td>
                                <a href="{{ route('admin.teachers.edit', $teacher) }}" class="btn btn-xs">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                                <form action="{{ route('admin.teachers.destroy', $teacher) }}" 
                                      method="POST" 
                                      style="display: inline-block;"
                                      onsubmit="return confirm('Are you sure you want to delete this teacher?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-xs btn-danger">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="pagination">
            {{ $teachers->links() }}
        </div>
    @endif
</div>
@endsection

