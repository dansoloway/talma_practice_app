@extends('layouts.admin')

@section('title', 'Organizations')

@section('content')
<div class="container">
    <div class="page-header">
        <h1 class="page-title">Organizations</h1>
        <a href="{{ route('admin.organizations.create') }}" class="btn btn-primary">Create Organization</a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    @if($organizations->count() > 0)
        <table class="table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Slug</th>
                    <th>Access</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($organizations as $org)
                    <tr>
                        <td><strong>{{ $org->name }}</strong></td>
                        <td><code>{{ $org->slug }}</code></td>
                        <td>
                            <span class="badge badge-{{ $org->access_mode === 'open' ? 'success' : 'secondary' }}">
                                {{ $org->access_mode === 'open' ? 'Open (public)' : 'Restricted' }}
                            </span>
                        </td>
                        <td>
                            <span class="badge badge-{{ $org->is_active ? 'primary' : 'danger' }}">
                                {{ $org->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td>
                            <a href="{{ route('admin.organizations.edit', $org) }}" class="btn btn-sm">Edit</a>
                            <a href="{{ route('org.student.index', $org->slug) }}" class="btn btn-sm" target="_blank" rel="noopener">View student page</a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <div class="bg-white rounded-2xl border border-gray-200 p-8 text-center">
            <p class="text-gray-600 mb-4">No organizations yet.</p>
            <a href="{{ route('admin.organizations.create') }}" class="btn btn-primary">Create your first organization</a>
        </div>
    @endif
</div>
@endsection
