@extends('layouts.admin')

@section('title', 'Terms and Conditions')

@section('content')
<div class="container">
    <div class="page-header">
        <h1 class="page-title">Terms and Conditions</h1>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <p class="text-gray-600 mb-6">Manage terms of use and privacy policy documents shown on registration. Each document uses the signup page language.</p>

    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Title</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Version</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Updated</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($terms as $term)
                    <tr>
                        <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ ucfirst(str_replace('_', ' ', $term->type)) }}</td>
                        <td class="px-6 py-4 text-sm text-gray-600">{{ $term->title }}</td>
                        <td class="px-6 py-4 text-sm text-gray-600">{{ $term->version }}</td>
                        <td class="px-6 py-4 text-sm">
                            @if($term->active)
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Active</span>
                            @else
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">Inactive</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-600">{{ $term->updated_at->format('M j, Y') }}</td>
                        <td class="px-6 py-4 text-sm">
                            <a href="{{ route('admin.terms-and-conditions.edit', $term) }}" class="text-blue-600 hover:text-blue-800">Edit</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-8 text-center text-sm text-gray-500">
                            No terms found. Run <code>php artisan db:seed --class=TermsAndConditionsSeeder</code>.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
