# Clause Exercise Refactor - Complete Solution

## (1) UPDATED STEP 2 PROMPTS

### System Message:
```
You are an educational content creator. Analyze a complete paragraph and strategically add fill-in-the-blank exercises. Extract the actual words from the paragraph as correct answers. You must use explicit blank identifiers in the format {{blank_1}}, {{blank_2}}, etc. Each blank must have a unique identifier that matches between the paragraph and the blanks array.
```

### User Prompt Template:
```
Analyze this complete paragraph and add fill-in-the-blank exercises to it.

COMPLETE PARAGRAPH (no blanks yet):
{completeParagraph}

VOCABULARY WORDS USED IN PARAGRAPH:
{vocabUsedText}

GRAMMAR CONCEPTS DEMONSTRATED IN PARAGRAPH:
{grammarUsedText}

ALL AVAILABLE VOCABULARY WORDS:
{vocabList}

ALL AVAILABLE GRAMMAR CONCEPTS:
{grammarConceptsText}

CRITICAL REQUIREMENTS:
1. Add exactly {blankCount} blanks to the paragraph by replacing words/phrases with {{blank_1}}, {{blank_2}}, {{blank_3}}, etc.
2. Use EXACTLY these token formats: {{blank_1}}, {{blank_2}}, {{blank_3}}, ... {{blank_{N}}}
3. Extract the ACTUAL word/phrase from the paragraph as the correct answer for each blank
4. Include at least 1 vocabulary blank (replace a vocabulary word with {{blank_X}})
5. Include at least 1 grammar blank (replace a word/phrase that demonstrates a grammar concept with {{blank_X}})
6. Choose strategic locations for blanks - words that are important for understanding
7. For vocabulary blanks: The correct answer must be a word from the vocabulary_used list
8. For grammar blanks: The correct answer must demonstrate one of the grammar_concepts_used
9. Maintain the paragraph's grammatical correctness and flow
10. The paragraph MUST contain exactly {blankCount} tokens in the format {{blank_X}}
11. Each blank_id (blank_1, blank_2, etc.) must appear exactly once in the paragraph

Return JSON with:
- paragraph: The paragraph with {{blank_1}}, {{blank_2}}, etc. tokens added (exactly {blankCount} tokens)
- blanks: Array of exactly {blankCount} objects, each with:
  - blank_id: "blank_1", "blank_2", etc. (MUST match the tokens in paragraph)
  - type: Either "vocab" or "grammar"
  - correct_answer: The ACTUAL word/phrase from the original paragraph that you replaced with {{blank_X}}
  - correct_vocab_text: For vocab blanks, this MUST equal correct_answer (the word string)
  - correct_vocab_id: For vocab blanks, optional vocabulary ID if you can identify it from the vocabulary_used list
  - sentence_context: The full sentence containing this blank, with {{blank_X}} showing where the blank is
  - grammar_concept_id: Required for grammar blanks - The ID of the grammar concept being tested
  - grammar_concept: Required for grammar blanks - The display name of the grammar concept
```

### JSON Schema for Step 2:
```json
{
  "type": "object",
  "required": ["paragraph", "blanks"],
  "properties": {
    "paragraph": {
      "type": "string",
      "description": "The paragraph with {{blank_1}}, {{blank_2}}, etc. tokens. Must contain exactly {blankCount} tokens."
    },
    "blanks": {
      "type": "array",
      "minItems": {blankCount},
      "maxItems": {blankCount},
      "items": {
        "type": "object",
        "required": ["blank_id", "type", "correct_answer", "sentence_context"],
        "properties": {
          "blank_id": {
            "type": "string",
            "pattern": "^blank_\\d+$",
            "description": "Unique identifier like 'blank_1', 'blank_2', etc. Must match tokens in paragraph."
          },
          "type": {
            "type": "string",
            "enum": ["vocab", "grammar"]
          },
          "correct_answer": {
            "type": "string",
            "description": "The actual word/phrase from the original paragraph"
          },
          "correct_vocab_text": {
            "type": "string",
            "description": "For vocab blanks: the word text (must equal correct_answer)"
          },
          "correct_vocab_id": {
            "type": "integer",
            "description": "For vocab blanks: optional vocabulary ID"
          },
          "sentence_context": {
            "type": "string",
            "description": "The full sentence containing {{blank_X}} token"
          },
          "grammar_concept_id": {
            "type": "integer",
            "description": "Required for grammar blanks"
          },
          "grammar_concept": {
            "type": "string",
            "description": "Required for grammar blanks"
          }
        }
      }
    }
  }
}
```

---

## (2) UPDATED STEP 3 PROMPTS

### System Message (Vocab):
```
You are an educational content creator. Generate appropriate distractors for a vocabulary fill-in-the-blank exercise. The correct answer is already known from the sentence. Generate distractors that are grammatically incorrect or contextually wrong when placed in the sentence. All distractors must come from the provided vocabulary list.
```

### User Prompt Template (Vocab):
```
Generate distractors for a vocabulary fill-in-the-blank exercise.

SENTENCE WITH BLANK:
{sentenceContext}

CORRECT ANSWER (extracted from the original paragraph):
{correctAnswer}

AVAILABLE VOCABULARY WORDS:
{vocabList}

REQUIREMENTS:
1. The correct answer is already known: "{correctAnswer}"
2. Generate exactly 3 distractors from the vocabulary list (exclude the correct answer)
3. All distractors MUST be grammatically incorrect or contextually wrong when placed in the sentence
4. Test each distractor: Read the sentence with the distractor inserted. Does it make sense? If yes, it's not a good distractor
5. Choose distractors that are plausible but clearly wrong in this context
6. No distractor should duplicate the correct answer

Return JSON with:
- distractors: Array of exactly 3 vocabulary words (strings) that don't fit in the sentence
```

### System Message (Grammar):
```
You are an educational content creator. Generate appropriate distractors for a grammar fill-in-the-blank exercise. The correct answer is already known from the sentence. Generate distractors that are grammatically incorrect when placed in the sentence. The correct answer must be the ONLY grammatically correct option.
```

### User Prompt Template (Grammar):
```
Generate distractors for a grammar fill-in-the-blank exercise.

SENTENCE WITH BLANK:
{sentenceContext}

CORRECT ANSWER (extracted from the original paragraph):
{correctAnswer}

GRAMMAR CONCEPT BEING TESTED:
Grammar Concept ID {grammarConceptId}: {grammarConcept}

REQUIREMENTS:
1. The correct answer is already known: "{correctAnswer}"
2. Generate exactly 3 distractors that are grammatically incorrect when placed in this sentence
3. Test each distractor: Read the sentence with the distractor inserted. Is it grammatically wrong? If it could be correct, replace it
4. Distractors should be wrong forms:
   - Wrong tense (e.g., if correct is past tense, use present or future)
   - Wrong verb form (e.g., if correct is "ask", use "asking" or "asked" if wrong)
   - Wrong modal (e.g., if correct is "should", use "must", "will", "can" if wrong)
5. The correct answer must be the ONLY grammatically correct option for this blank
6. Each distractor must be clearly grammatically incorrect when placed in the sentence

Return JSON with:
- distractors: Array of exactly 3 grammatically incorrect options (strings)
```

### JSON Schema for Step 3:
```json
{
  "type": "object",
  "required": ["distractors"],
  "properties": {
    "distractors": {
      "type": "array",
      "items": {"type": "string"},
      "minItems": 3,
      "maxItems": 3,
      "description": "Exactly 3 incorrect options"
    }
  }
}
```

---

## (3) BACKEND REFACTOR

### Storage Format Change

**New `blanks` structure (single source of truth):**
```php
// Store as JSON in clause_exercises.blanks column
[
    "blank_1" => [
        "type" => "vocab",
        "correct" => [
            "text" => "adapt",           // Always stored
            "vocab_id" => 123            // Optional, for reference
        ],
        "distractors" => [
            ["text" => "survive", "vocab_id" => 124],
            ["text" => "migrate", "vocab_id" => 125],
            ["text" => "hibernate", "vocab_id" => 126]
        ],
        "sentence_context" => "Animals must {{blank_1}} to survive."
    ],
    "blank_2" => [
        "type" => "grammar",
        "correct" => [
            "text" => "were doing"
        ],
        "distractors" => [
            ["text" => "did"],
            ["text" => "do"],
            ["text" => "was doing"]
        ],
        "grammar_concept_id" => 5,
        "grammar_concept" => "Past Progressive - were doing",
        "sentence_context" => "Yesterday, we {{blank_2}} our homework."
    ]
]
```

**Migration:**
- Keep `paragraph_text` with {{blank_id}} tokens
- Store `blanks` as object keyed by blank_id
- `correct_answers` and `blank_positions` become derived/computed fields (or removed)

### Code Snippets

#### Token Extraction Function:
```php
/**
 * Extract all {{blank_id}} tokens from paragraph text
 */
protected function extractBlankTokens(string $paragraph): array
{
    preg_match_all('/\{\{(\w+)\}\}/', $paragraph, $matches);
    return $matches[1] ?? []; // Returns ['blank_1', 'blank_2', ...]
}
```

#### Validator Function:
```php
/**
 * Validate and repair exercise data before saving
 */
protected function validateAndRepairExercise(
    string $paragraph,
    array $blanks,
    array $vocabulary,
    int $expectedBlankCount
): array {
    $tokens = $this->extractBlankTokens($paragraph);
    $blankIds = array_keys($blanks);
    
    $errors = [];
    $repairs = [];
    
    // Check token/blank consistency
    $missingTokens = array_diff($tokens, $blankIds);
    $orphanBlanks = array_diff($blankIds, $tokens);
    
    if (!empty($missingTokens)) {
        $errors[] = "Paragraph contains tokens without blanks: " . implode(', ', $missingTokens);
        // Repair: Remove orphan tokens from paragraph
        foreach ($missingTokens as $token) {
            $paragraph = str_replace("{{{$token}}}", '', $paragraph);
            $repairs[] = "Removed orphan token: {$token}";
        }
    }
    
    if (!empty($orphanBlanks)) {
        $errors[] = "Blanks exist without tokens: " . implode(', ', $orphanBlanks);
        // Repair: Remove orphan blanks
        foreach ($orphanBlanks as $blankId) {
            unset($blanks[$blankId]);
            $repairs[] = "Removed orphan blank: {$blankId}";
        }
    }
    
    // Validate each blank
    foreach ($blanks as $blankId => $blank) {
        $blankErrors = [];
        
        // Check type
        if (!in_array($blank['type'] ?? '', ['vocab', 'grammar'])) {
            $blankErrors[] = "Invalid type";
        }
        
        // Check correct answer exists
        if (empty($blank['correct']['text'] ?? '')) {
            $blankErrors[] = "Missing correct answer text";
        }
        
        // Check distractors count
        $distractors = $blank['distractors'] ?? [];
        if (count($distractors) !== 3) {
            $blankErrors[] = "Expected 3 distractors, found " . count($distractors);
        }
        
        // Check for duplicates
        $allOptions = array_merge(
            [$blank['correct']['text']],
            array_column($distractors, 'text')
        );
        if (count($allOptions) !== count(array_unique($allOptions))) {
            $blankErrors[] = "Duplicate options found";
        }
        
        // Vocab-specific checks
        if ($blank['type'] === 'vocab') {
            if (empty($blank['correct']['text'])) {
                $blankErrors[] = "Vocab blank missing correct text";
            }
            // Ensure vocab text matches correct_answer if provided
            if (isset($blank['correct_answer']) && $blank['correct']['text'] !== $blank['correct_answer']) {
                $repairs[] = "Fixed vocab text mismatch for {$blankId}";
                $blank['correct']['text'] = $blank['correct_answer'];
            }
        }
        
        // Grammar-specific checks
        if ($blank['type'] === 'grammar') {
            if (empty($blank['grammar_concept_id']) || empty($blank['grammar_concept'])) {
                $blankErrors[] = "Grammar blank missing concept info";
            }
        }
        
        if (!empty($blankErrors)) {
            $errors[] = "Blank {$blankId}: " . implode(', ', $blankErrors);
        }
    }
    
    // Check blank count
    $actualCount = count($blanks);
    if ($actualCount !== $expectedBlankCount) {
        $errors[] = "Expected {$expectedBlankCount} blanks, found {$actualCount}";
    }
    
    // Check vocab/grammar mix
    $vocabCount = count(array_filter($blanks, fn($b) => ($b['type'] ?? '') === 'vocab'));
    $grammarCount = count(array_filter($blanks, fn($b) => ($b['type'] ?? '') === 'grammar'));
    
    if ($vocabCount === 0 || $grammarCount === 0) {
        $errors[] = "Must have at least 1 vocab and 1 grammar blank";
    }
    
    return [
        'valid' => empty($errors),
        'errors' => $errors,
        'repairs' => $repairs,
        'paragraph' => $paragraph,
        'blanks' => $blanks,
    ];
}
```

#### Fallback Distractor Generator:
```php
/**
 * Generate fallback distractors when AI fails
 */
protected function generateFallbackDistractors(
    string $blankType,
    string $correctAnswer,
    array $vocabulary,
    ?int $grammarConceptId = null,
    ?string $grammarConcept = null
): array {
    if ($blankType === 'vocab') {
        // For vocab: randomly select 3 from available vocab, excluding correct
        $availableVocab = array_filter($vocabulary, function($vocab) use ($correctAnswer) {
            return strtolower($vocab['word'] ?? '') !== strtolower($correctAnswer);
        });
        
        if (count($availableVocab) < 3) {
            // Not enough vocab, use generic wrong words
            return ['different', 'wrong', 'incorrect'];
        }
        
        $selected = array_rand($availableVocab, min(3, count($availableVocab)));
        return array_map(function($idx) use ($availableVocab) {
            return $availableVocab[$idx]['word'];
        }, is_array($selected) ? $selected : [$selected]);
        
    } else {
        // For grammar: rule-based wrong forms
        $correct = strtolower($correctAnswer);
        
        // Tense-based fallbacks
        if (preg_match('/\b(was|were|did|had)\b/', $correct)) {
            // Past tense -> wrong tenses
            return ['is', 'will', 'has'];
        } elseif (preg_match('/\b(is|are|am)\b/', $correct)) {
            // Present -> wrong tenses
            return ['was', 'were', 'will'];
        } elseif (preg_match('/\b(will|shall)\b/', $correct)) {
            // Future -> wrong tenses
            return ['was', 'is', 'has'];
        }
        
        // Modal fallbacks
        $modals = ['should', 'must', 'can', 'will', 'would', 'could', 'may', 'might'];
        if (in_array($correct, $modals)) {
            $wrongModals = array_filter($modals, fn($m) => $m !== $correct);
            return array_slice(array_values($wrongModals), 0, 3);
        }
        
        // Verb form fallbacks
        if (preg_match('/ing$/', $correct)) {
            // -ing form -> wrong forms
            return [str_replace('ing', '', $correct), str_replace('ing', 'ed', $correct), $correct . 's'];
        } elseif (preg_match('/ed$/', $correct)) {
            // -ed form -> wrong forms
            return [str_replace('ed', '', $correct), str_replace('ed', 'ing', $correct), $correct . 's'];
        }
        
        // Generic fallback
        return ['wrong', 'incorrect', 'different'];
    }
}
```

#### Step 3 with Robust Error Handling:
```php
// Step 3: Generate distractors for each blank
$finalBlanks = [];
foreach ($blankInfoArray as $blankInfo) {
    $blankId = $blankInfo['blank_id'] ?? null;
    if (!$blankId) {
        Log::error('Blank missing blank_id', ['blank_info' => $blankInfo]);
        continue; // Skip if no ID
    }
    
    $blankType = $blankInfo['type'] ?? 'vocab';
    $correctAnswer = trim($blankInfo['correct_answer'] ?? '');
    $sentenceContext = $blankInfo['sentence_context'] ?? '';
    
    if (empty($correctAnswer)) {
        Log::warning('Blank missing correct answer', ['blank_id' => $blankId]);
        // Repair: Remove this blank from paragraph
        $paragraph = str_replace("{{{$blankId}}}", $correctAnswer, $paragraph);
        continue;
    }
    
    // Try AI generation
    $distractors = null;
    try {
        $distractorsPrompt = $this->buildDistractorsPrompt(...);
        $distractorsResponse = $this->openAiService->chatCompletion(...);
        $distractorsData = json_decode($this->openAiService->extractContent($distractorsResponse), true);
        
        if (isset($distractorsData['distractors']) && count($distractorsData['distractors']) === 3) {
            $distractors = array_map('trim', $distractorsData['distractors']);
        }
    } catch (\Exception $e) {
        Log::warning('AI distractor generation failed', [
            'blank_id' => $blankId,
            'error' => $e->getMessage()
        ]);
    }
    
    // Fallback if AI failed
    if (!$distractors || count($distractors) !== 3) {
        Log::info('Using fallback distractors', ['blank_id' => $blankId]);
        $distractors = $this->generateFallbackDistractors(
            $blankType,
            $correctAnswer,
            $vocabList,
            $blankInfo['grammar_concept_id'] ?? null,
            $blankInfo['grammar_concept'] ?? null
        );
    }
    
    // Build blank entry
    $blankEntry = [
        'type' => $blankType,
        'correct' => ['text' => $correctAnswer],
        'distractors' => array_map(function($dist) {
            return ['text' => $dist];
        }, $distractors),
        'sentence_context' => $sentenceContext,
    ];
    
    // Add vocab-specific data
    if ($blankType === 'vocab') {
        $blankEntry['correct']['vocab_id'] = $blankInfo['correct_vocab_id'] ?? null;
        // Match distractors to vocab IDs
        foreach ($blankEntry['distractors'] as $idx => $dist) {
            $vocabMatch = collect($vocabulary)->first(function($vocab) use ($dist) {
                return strtolower($vocab['word']) === strtolower($dist['text']);
            });
            if ($vocabMatch) {
                $blankEntry['distractors'][$idx]['vocab_id'] = $vocabMatch['id'];
            }
        }
    }
    
    // Add grammar-specific data
    if ($blankType === 'grammar') {
        $blankEntry['grammar_concept_id'] = $blankInfo['grammar_concept_id'] ?? null;
        $blankEntry['grammar_concept'] = $blankInfo['grammar_concept'] ?? '';
    }
    
    $finalBlanks[$blankId] = $blankEntry;
}

// Validate and repair
$validation = $this->validateAndRepairExercise(
    $paragraph,
    $finalBlanks,
    $vocabulary,
    $blankCount
);

if (!$validation['valid']) {
    throw new \Exception("Exercise validation failed: " . implode('; ', $validation['errors']));
}

// Log repairs if any
if (!empty($validation['repairs'])) {
    Log::info('Exercise repairs applied', ['repairs' => $validation['repairs']]);
}

return [
    'paragraph_text' => $validation['paragraph'],
    'blanks' => $validation['blanks'],
];
```

---

## (4) FRONTEND REFACTOR

### Token Parsing & Rendering:
```javascript
// Render paragraph with blanks
function renderParagraph() {
    const container = document.getElementById('paragraph-container');
    let paragraph = exerciseData.paragraph_text || '';
    const blanks = exerciseData.blanks || {}; // Object keyed by blank_id
    
    // Extract all {{blank_id}} tokens
    const tokenRegex = /\{\{(\w+)\}\}/g;
    const tokens = [];
    let match;
    while ((match = tokenRegex.exec(paragraph)) !== null) {
        tokens.push(match[1]); // blank_1, blank_2, etc.
    }
    
    if (tokens.length === 0) {
        container.innerHTML = `<div class="alert alert-error">
            <p><strong>Error:</strong> This exercise is missing blank placeholders.</p>
        </div>`;
        return;
    }
    
    // Replace each token with dropdown
    tokens.forEach(token => {
        const blank = blanks[token];
        
        if (!blank) {
            console.warn(`Missing blank data for token: ${token}`);
            paragraph = paragraph.replace(
                `{{${token}}}`,
                '<select class="blank-select" disabled data-blank-id="' + token + '"><option>Blank unavailable</option></select>'
            );
            return;
        }
        
        // Build options
        let options = [];
        
        if (blank.type === 'vocab') {
            // Use stored text, not lookup
            const correctText = blank.correct.text;
            options.push({value: correctText, label: correctText, isCorrect: true});
            
            blank.distractors.forEach(dist => {
                options.push({value: dist.text, label: dist.text, isCorrect: false});
            });
        } else if (blank.type === 'grammar') {
            const correctText = blank.correct.text;
            options.push({value: correctText, label: correctText, isCorrect: true});
            
            blank.distractors.forEach(dist => {
                options.push({value: dist.text, label: dist.text, isCorrect: false});
            });
        }
        
        // Shuffle options
        const shuffled = options.sort(() => Math.random() - 0.5);
        
        const optionsHtml = shuffled.map(opt => 
            `<option value="${escapeHtml(opt.value)}">${escapeHtml(opt.label)}</option>`
        ).join('');
        
        const selectHtml = `<select class="blank-select" data-blank-id="${token}" data-blank-type="${blank.type}" id="${token}">
            <option value="">_____</option>
            ${optionsHtml}
        </select>`;
        
        paragraph = paragraph.replace(`{{${token}}}`, selectHtml);
    });
    
    container.innerHTML = `<div class="paragraph-text">${paragraph}</div>`;
    setupBlankSelects();
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
```

### Updated Exercise Data Structure (from backend):
```javascript
const exerciseData = {
    paragraph_text: "Animals must {{blank_1}} to survive. For example, {{blank_2}}, which can be found in deserts...",
    blanks: {
        "blank_1": {
            type: "vocab",
            correct: {
                text: "adapt",
                vocab_id: 123
            },
            distractors: [
                {text: "survive", vocab_id: 124},
                {text: "migrate", vocab_id: 125},
                {text: "hibernate", vocab_id: 126}
            ],
            sentence_context: "Animals must {{blank_1}} to survive."
        },
        "blank_2": {
            type: "grammar",
            correct: {
                text: "were doing"
            },
            distractors: [
                {text: "did"},
                {text: "do"},
                {text: "was doing"}
            ],
            grammar_concept_id: 5,
            grammar_concept: "Past Progressive - were doing",
            sentence_context: "Yesterday, we {{blank_2}} our homework."
        }
    }
};
```

### Backward Compatibility Adapter:
```php
// In ClauseExercise model or controller
public function getBlanksAttribute($value)
{
    $blanks = json_decode($value, true) ?? [];
    
    // If old format (blank_metadata), convert
    if (isset($blanks['blank_1']) && isset($blanks['blank_1']['type'])) {
        // Already new format
        return $blanks;
    }
    
    // Old format: convert blank_metadata + correct_answers to new format
    $oldMetadata = $blanks;
    $correctAnswers = $this->correct_answers ?? [];
    $converted = [];
    
    foreach ($oldMetadata as $blankId => $metadata) {
        $converted[$blankId] = [
            'type' => $metadata['type'] ?? 'vocab',
            'correct' => [
                'text' => $correctAnswers[$blankId] ?? '',
                'vocab_id' => $metadata['correct_answer'] ?? null,
            ],
            'distractors' => array_map(function($dist) {
                return ['text' => is_string($dist) ? $dist : '', 'vocab_id' => is_numeric($dist) ? $dist : null];
            }, $metadata['distractors'] ?? []),
        ];
        
        if ($metadata['type'] === 'grammar') {
            $converted[$blankId]['grammar_concept_id'] = $metadata['grammar_concept_id'] ?? null;
            $converted[$blankId]['grammar_concept'] = $metadata['grammar_concept'] ?? '';
        }
    }
    
    return $converted;
}
```

---

## IMPLEMENTATION CHECKLIST

- [ ] Update Step 2 prompt builder to use {{blank_id}} format
- [ ] Update Step 2 JSON schema to require blank_id
- [ ] Update Step 3 prompt builders (vocab + grammar)
- [ ] Implement token extraction function
- [ ] Implement validator/repair function
- [ ] Implement fallback distractor generator
- [ ] Refactor Step 3 loop to use fallbacks instead of continue
- [ ] Update storage format to use new blanks structure
- [ ] Add backward compatibility adapter
- [ ] Update frontend renderParagraph() to parse {{blank_id}} tokens
- [ ] Update frontend to use blanks[blank_id] instead of index mapping
- [ ] Update frontend to use stored text instead of vocab ID lookups
- [ ] Add graceful fallback UI for missing blanks
- [ ] Test with old data (backward compatibility)
- [ ] Test with new data (token-based)
- [ ] Test fallback distractor generation
- [ ] Test validator/repair edge cases
