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
            'translations' => ['nullable', 'array'],
            'translations.he.title' => ['nullable', 'string', 'max:255'],
            'translations.he.content' => ['nullable', 'string'],
        ]);

        $validated['active'] = $request->boolean('active');

        $translations = $termsAndCondition->translations ?? [];
        $hebrewTitle = trim((string) ($validated['translations']['he']['title'] ?? ''));
        $hebrewContent = trim((string) ($validated['translations']['he']['content'] ?? ''));

        if ($hebrewTitle !== '' && $hebrewContent !== '') {
            $translations['he'] = [
                'title' => $hebrewTitle,
                'content' => $hebrewContent,
            ];
        } else {
            unset($translations['he']);
        }

        $termsAndCondition->update([
            'title' => $validated['title'],
            'content' => $validated['content'],
            'version' => $validated['version'],
            'active' => $validated['active'],
            'translations' => $translations,
        ]);

        return redirect()->route('admin.terms-and-conditions.index')
            ->with('success', 'Terms and conditions updated successfully.');
    }
}
