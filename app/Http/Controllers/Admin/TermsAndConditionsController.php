<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TermsAndCondition;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TermsAndConditionsController extends Controller
{
    public function index(): View
    {
        $terms = TermsAndCondition::orderBy('type')->orderByDesc('version')->get();

        return view('admin.terms-and-conditions.index', compact('terms'));
    }

    public function edit(TermsAndCondition $termsAndCondition): View
    {
        return view('admin.terms-and-conditions.edit', compact('termsAndCondition'));
    }

    public function update(Request $request, TermsAndCondition $termsAndCondition): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'version' => ['required', 'string', 'max:50'],
            'active' => ['sometimes', 'boolean'],
        ]);

        $validated['active'] = $request->boolean('active');

        $termsAndCondition->update($validated);

        return redirect()->route('admin.terms-and-conditions.index')
            ->with('success', 'Terms and conditions updated successfully.');
    }
}
