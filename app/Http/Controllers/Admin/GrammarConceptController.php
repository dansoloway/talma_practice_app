<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GrammarConcept;
use App\Models\GrammarSet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class GrammarConceptController extends Controller
{
    /**
     * Display a listing of grammar concepts.
     */
    public function index()
    {
        $grammarSets = GrammarSet::with('grammarConcepts')->orderBy('created_at', 'desc')->get();
        $concepts = GrammarConcept::ordered()->get();
        
        // Group by section and topic for better display
        $grouped = $concepts->groupBy(function ($concept) {
            return $concept->section . '|' . $concept->grammar_topic;
        });
        
        return view('admin.grammar-concepts.index', compact('concepts', 'grouped', 'grammarSets'));
    }

    /**
     * Show CSV upload form.
     */
    public function csvUpload()
    {
        return view('admin.grammar-concepts.csv-upload');
    }

    /**
     * Process CSV upload.
     */
    public function processCsv(Request $request)
    {
        try {
            $request->validate([
                'set_title' => 'required|string|max:255',
                'csv_file' => 'required|file|mimes:csv,txt|max:2048',
                'import_mode' => 'required|in:add,replace',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $e->errors(),
                ], 422);
            }
            
            return redirect()
                ->route('admin.grammar-concepts.csv.upload')
                ->withErrors($e->errors())
                ->withInput();
        }

        $file = $request->file('csv_file');
        $csvData = array_map('str_getcsv', file($file->getRealPath()));
        $importMode = $request->input('import_mode');
        $setTitle = $request->input('set_title', 'Grammar Set ' . date('Y-m-d H:i:s'));
        
        Log::info("Starting grammar concepts CSV import in {$importMode} mode");
        
        // Create or get grammar set
        $grammarSet = null;
        if ($importMode === 'replace') {
            // For replace mode, create a new set
            $grammarSet = GrammarSet::create([
                'title' => $setTitle,
                'description' => 'Imported from ' . $file->getClientOriginalName(),
                'source_file' => $file->getClientOriginalName(),
            ]);
            // Delete old concepts (they'll be replaced)
            GrammarConcept::whereNull('grammar_set_id')->delete();
        } else {
            // For add mode, create a new set for this import
            $grammarSet = GrammarSet::create([
                'title' => $setTitle,
                'description' => 'Imported from ' . $file->getClientOriginalName(),
                'source_file' => $file->getClientOriginalName(),
            ]);
        }
        
        $imported = 0;
        $errors = [];
        $processedConcepts = []; // Track to prevent duplicates within CSV
        
        foreach ($csvData as $index => $row) {
            // Skip header row
            if ($index === 0 && (
                strtolower(trim($row[0] ?? '')) === 'section' || 
                strtolower(trim($row[0] ?? '')) === 'grammar topic' ||
                strtolower(trim($row[0] ?? '')) === 'grammar sub topic'
            )) {
                continue;
            }

            // Validate row has required columns
            if (count($row) < 3) {
                $errors[] = "Row " . ($index + 1) . ": Missing required columns. Expected: Section, Grammar Topic, Grammar Sub Topic";
                continue;
            }

            $section = !empty(trim($row[0])) ? (int)trim($row[0]) : null;
            $grammarTopic = trim($row[1]);
            $grammarSubTopic = trim($row[2]);

            // Validate required fields
            if (empty($grammarTopic)) {
                $errors[] = "Row " . ($index + 1) . ": Grammar Topic is required";
                continue;
            }

            if (empty($grammarSubTopic)) {
                $errors[] = "Row " . ($index + 1) . ": Grammar Sub Topic is required";
                continue;
            }

            // Create unique key for duplicate checking
            $uniqueKey = strtolower($grammarTopic . '|' . $grammarSubTopic);
            
            // Check for duplicate within the CSV
            if (in_array($uniqueKey, $processedConcepts)) {
                $errors[] = "Row " . ($index + 1) . ": Duplicate concept '{$grammarTopic} - {$grammarSubTopic}' found in CSV";
                continue;
            }
            
            // Check for duplicate in existing database (only in add mode)
            if ($importMode === 'add') {
                $existing = GrammarConcept::where('grammar_topic', $grammarTopic)
                    ->where('grammar_sub_topic', $grammarSubTopic)
                    ->first();
                if ($existing) {
                    $errors[] = "Row " . ($index + 1) . ": Concept '{$grammarTopic} - {$grammarSubTopic}' already exists. Use 'Replace' mode to replace all concepts.";
                    continue;
                }
            }

            try {
                // Auto-generate title if not provided: "Grammar Topic - Grammar Sub Topic"
                $title = $grammarTopic . ' - ' . $grammarSubTopic;
                
                GrammarConcept::create([
                    'title' => $title,
                    'grammar_set_id' => $grammarSet->id,
                    'section' => $section,
                    'grammar_topic' => $grammarTopic,
                    'grammar_sub_topic' => $grammarSubTopic,
                ]);
                
                $processedConcepts[] = $uniqueKey;
                $imported++;
            } catch (\Exception $e) {
                $errors[] = "Row " . ($index + 1) . ": " . $e->getMessage();
                Log::error("Failed to create grammar concept from CSV row " . ($index + 1) . ": " . $e->getMessage());
            }
        }

        $action = $importMode === 'replace' ? 'replaced with' : 'added';
        $message = "Successfully {$action} {$imported} grammar concepts in set '{$grammarSet->title}'.";
        if (!empty($errors)) {
            $message .= " Errors: " . implode(', ', array_slice($errors, 0, 5)); // Show first 5 errors
            if (count($errors) > 5) {
                $message .= " (and " . (count($errors) - 5) . " more)";
            }
        }

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'imported' => $imported,
                'errors' => $errors,
            ]);
        }

        $redirect = redirect()
            ->route('admin.grammar-concepts.index')
            ->with('success', $message);
        
        // If there are errors, pass them as import_errors to avoid overwriting Laravel's $errors MessageBag
        if (!empty($errors)) {
            $redirect->with('import_errors', $errors);
        }
        
        return $redirect;
    }

    /**
     * Show the form for editing a grammar concept.
     */
    public function edit(GrammarConcept $grammarConcept)
    {
        return view('admin.grammar-concepts.edit', compact('grammarConcept'));
    }

    /**
     * Update the specified grammar concept.
     */
    public function update(Request $request, GrammarConcept $grammarConcept)
    {
        $validated = $request->validate([
            'title' => 'nullable|string|max:255',
            'section' => 'nullable|integer',
            'grammar_topic' => 'required|string|max:255',
            'grammar_sub_topic' => 'required|string|max:255',
        ]);

        // Auto-generate title if not provided
        if (empty($validated['title'])) {
            $validated['title'] = $validated['grammar_topic'] . ' - ' . $validated['grammar_sub_topic'];
        }

        $grammarConcept->update($validated);

        return redirect()
            ->route('admin.grammar-concepts.index')
            ->with('success', 'Grammar concept updated successfully!');
    }

    /**
     * Remove the specified grammar concept.
     */
    public function destroy(GrammarConcept $grammarConcept)
    {
        $grammarConcept->delete();

        return redirect()
            ->route('admin.grammar-concepts.index')
            ->with('success', 'Grammar concept deleted successfully!');
    }

    /**
     * Show the form for editing a grammar set.
     */
    public function editSet(GrammarSet $grammarSet)
    {
        return view('admin.grammar-sets.edit', compact('grammarSet'));
    }

    /**
     * Update the specified grammar set.
     */
    public function updateSet(Request $request, GrammarSet $grammarSet)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $grammarSet->update($validated);

        return redirect()
            ->route('admin.grammar-concepts.index')
            ->with('success', 'Grammar set updated successfully!');
    }
}
