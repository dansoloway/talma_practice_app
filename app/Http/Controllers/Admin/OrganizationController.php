<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class OrganizationController extends Controller
{
    public function index()
    {
        $organizations = Organization::orderBy('name')->get();
        return view('admin.organizations.index', compact('organizations'));
    }

    public function create()
    {
        return view('admin.organizations.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => ['nullable', 'string', 'max:255', 'unique:organizations,slug'],
            'description' => 'nullable|string|max:65535',
            'access_mode' => ['required', Rule::in(['open', 'restricted'])],
            'is_active' => 'boolean',
        ]);

        $validated['slug'] = $validated['slug'] ?? Str::slug($validated['name']);
        $validated['is_active'] = $request->boolean('is_active', true);

        $org = Organization::create($validated);

        return redirect()
            ->route('admin.organizations.index')
            ->with('success', "Organization \"{$org->name}\" created successfully.");
    }

    public function edit(Organization $organization)
    {
        return view('admin.organizations.edit', compact('organization'));
    }

    public function update(Request $request, Organization $organization)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('organizations', 'slug')->ignore($organization->id)],
            'description' => 'nullable|string|max:65535',
            'access_mode' => ['required', Rule::in(['open', 'restricted'])],
            'is_active' => 'boolean',
        ]);

        $validated['slug'] = $validated['slug'] ?? Str::slug($validated['name']);
        $validated['is_active'] = $request->boolean('is_active', true);

        $organization->update($validated);

        return redirect()
            ->route('admin.organizations.index')
            ->with('success', "Organization \"{$organization->name}\" updated successfully.");
    }
}
