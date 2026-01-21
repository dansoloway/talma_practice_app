@extends('layouts.admin')

@section('title', 'Edit Grammar Set')

@section('content')
<div class="container">
    <div class="page-header">
        <h1 class="page-title">Edit Grammar Set</h1>
        <a href="{{ route('admin.grammar-concepts.index') }}" class="btn">Back to Grammar Sets</a>
    </div>

    <div class="card">
        <div class="card-body">
            <form action="{{ route('admin.grammar-sets.update', $grammarSet) }}" method="POST" class="form">
                @csrf
                @method('PUT')

                <div class="form-group">
                    <label for="title">Title *</label>
                    <input type="text" id="title" name="title" value="{{ old('title', $grammarSet->title) }}" required class="form-control">
                    <small>The name of this grammar set</small>
                </div>

                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" rows="3" class="form-control">{{ old('description', $grammarSet->description) }}</textarea>
                    <small>Optional description for this grammar set</small>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Update Grammar Set</button>
                    <a href="{{ route('admin.grammar-concepts.index') }}" class="btn">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
