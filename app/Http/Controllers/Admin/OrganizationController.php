<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

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
        $validated = $this->validateOrganization($request);

        $validated['slug'] = $validated['slug'] ?? Str::slug($validated['name']);
        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['allow_self_registration'] = $request->boolean('allow_self_registration', false);
        $validated['retain_voice_recordings'] = $request->boolean('retain_voice_recordings', false);

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
        $validated = $this->validateOrganization($request, $organization);

        $validated['slug'] = $validated['slug'] ?? Str::slug($validated['name']);
        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['allow_self_registration'] = $request->boolean('allow_self_registration', false);
        $validated['retain_voice_recordings'] = $request->boolean('retain_voice_recordings', false);

        $organization->update($validated);

        return redirect()
            ->route('admin.organizations.index')
            ->with('success', "Organization \"{$organization->name}\" updated successfully.");
    }

    protected function validateOrganization(Request $request, ?Organization $organization = null): array
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('organizations', 'slug')->ignore($organization?->id)],
            'description' => 'nullable|string|max:65535',
            'access_mode' => ['required', Rule::in(['open', 'restricted'])],
            'registration_type' => ['required', Rule::in([
                Organization::REGISTRATION_TYPE_STUDENT,
                Organization::REGISTRATION_TYPE_PARENT_SIGNUP,
            ])],
            'is_active' => 'boolean',
            'allow_self_registration' => 'boolean',
            'retain_voice_recordings' => 'boolean',
        ]);

        $allowSelfReg = $request->boolean('allow_self_registration', false);
        if ($validated['registration_type'] === Organization::REGISTRATION_TYPE_PARENT_SIGNUP) {
            if ($validated['access_mode'] !== 'restricted') {
                throw ValidationException::withMessages([
                    'registration_type' => 'Parent/guardian signup requires restricted access mode.',
                ]);
            }
            if (! $allowSelfReg) {
                throw ValidationException::withMessages([
                    'allow_self_registration' => 'Parent/guardian signup requires self-registration to be enabled.',
                ]);
            }
        }

        return $validated;
    }
}
