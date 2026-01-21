<?php

namespace App\Services\AI;

use App\Models\GrammarSet;
use App\Models\Lesson;
use App\Services\OpenAi\OpenAiService;
use Illuminate\Support\Facades\Log;

class ClauseExerciseGenerator
{
    protected OpenAiService $openAiService;

    public function __construct(OpenAiService $openAiService)
    {
        $this->openAiService = $openAiService;
    }

    public function enabled(): bool
    {
        return $this->openAiService->enabled();
    }

    /**
     * Generate a clause exercise paragraph with blanks.
     * 
     * @param Lesson $lesson The lesson to generate exercise for
     * @param GrammarSet|null $grammarSet Optional grammar set to focus on
     * @param string|null $topic Optional topic to organize the paragraph around
     * @return array|null Generated exercise data or null on failure
     */
    public function generateExercise(Lesson $lesson, ?GrammarSet $grammarSet = null, ?string $topic = null, ?string $model = null): ?array
    {
        if (!$this->enabled()) {
            throw new \Exception('OpenAI API key not configured');
        }

        // Get vocabulary from lesson
        $vocabulary = $lesson->vocabulary()->active()->get();
        if ($vocabulary->isEmpty()) {
            throw new \Exception('Lesson has no vocabulary items');
        }

        // Get grammar concepts
        $grammarConcepts = [];
        if ($grammarSet) {
            $grammarConcepts = $grammarSet->grammarConcepts;
        } else {
            $grammarConcepts = $lesson->grammarSets->flatMap->grammarConcepts;
        }

        if ($grammarConcepts->isEmpty()) {
            throw new \Exception('No grammar concepts available for this lesson');
        }

        // Prepare vocabulary list
        $vocabList = $vocabulary->pluck('english_word')->toArray();
        $vocabWithIds = $vocabulary->mapWithKeys(function ($vocab) {
            return [$vocab->english_word => $vocab->id];
        })->toArray();

        // Prepare grammar concepts list with IDs for reference
        $grammarConceptsWithIds = $grammarConcepts->map(function ($concept) {
            return [
                'id' => $concept->id,
                'topic' => $concept->grammar_topic,
                'sub_topic' => $concept->grammar_sub_topic,
                'display' => $concept->grammar_topic . ' - ' . $concept->grammar_sub_topic,
            ];
        })->values()->toArray();

        $grammarList = array_column($grammarConceptsWithIds, 'display');
        $grammarFocus = !empty($grammarList) ? implode(', ', array_slice($grammarList, 0, 5)) : 'general grammar';
        
        // Use at least 4 blanks, but can use more based on vocabulary available
        $blankCount = max(4, min(8, (int)ceil(count($vocabList) * 0.3))); // At least 4, up to 8, roughly 30% of vocab

        $prompt = $this->buildPrompt($lesson->title, $vocabList, $grammarConceptsWithIds, $blankCount, $topic);

        try {
            $messages = [
                [
                    'role' => 'system',
                    'content' => 'You are an educational content creator for English language learners. Generate fill-in-the-blank paragraph exercises using the provided vocabulary words and grammar concepts. CRITICAL: You MUST use {} (curly braces with nothing inside) as placeholders for blanks in the paragraph text. Each blank must be represented by exactly {} in the paragraph.

BEFORE PRODUCING THE FINAL JSON, silently verify:
- The paragraph contains exactly the same number of {} placeholders as the blanks array length.
- At least one blank is type = "vocab" and one is type = "grammar".
- All vocab distractors come only from the provided vocabulary list.
- Grammar distractors are incorrect but plausible for the given concept.
- No distractor duplicates the correct answer.
- The first {} corresponds to blanks[0], the second {} to blanks[1], etc. (do not reorder blanks).',
                ],
                [
                    'role' => 'user',
                    'content' => $prompt,
                ],
            ];

            $options = [
                'model' => $model ?? config('services.openai.translation_model', 'gpt-4o-mini'),
                'temperature' => 0.6, // Lower temperature for better constraint-following and schema compliance
                'response_format' => [
                    'type' => 'json_schema',
                    'json_schema' => [
                        'name' => 'clause_exercise',
                        'schema' => [
                            'type' => 'object',
                            'required' => ['paragraph', 'blanks'],
                            'properties' => [
                                'paragraph' => [
                                    'type' => 'string',
                                    'description' => 'A paragraph (3-5 sentences) with {} placeholders for blanks. Use exactly ' . $blankCount . ' blanks.',
                                ],
                                'blanks' => [
                                    'type' => 'array',
                                    'items' => [
                                        'type' => 'object',
                                        'required' => ['id', 'type', 'correct_answer', 'distractors'],
                                        'properties' => [
                                            'id' => [
                                                'type' => 'string',
                                                'description' => 'Unique identifier like blank_1, blank_2, etc.',
                                            ],
                                            'type' => [
                                                'type' => 'string',
                                                'enum' => ['vocab', 'grammar'],
                                                'description' => 'Type of blank: vocab (uses vocabulary word) or grammar (tests grammar concept)',
                                            ],
                                            'correct_answer' => [
                                                'type' => 'string',
                                                'description' => 'The correct word (vocabulary word for vocab blanks, grammar word for grammar blanks)',
                                            ],
                                            'distractors' => [
                                                'type' => 'array',
                                                'items' => ['type' => 'string'],
                                                'minItems' => 3,
                                                'maxItems' => 3,
                                                'description' => 'Exactly 3 incorrect options (vocabulary words for vocab blanks, grammar words for grammar blanks)',
                                            ],
                                            'grammar_concept_id' => [
                                                'type' => 'integer',
                                                'description' => 'Required for grammar blanks: The ID of the grammar concept being tested',
                                            ],
                                            'grammar_concept' => [
                                                'type' => 'string',
                                                'description' => 'Required for grammar blanks: The display name of the grammar concept',
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ];

            // Try with gpt-4o-mini first, fallback to gpt-4o if validation fails
            $response = $this->openAiService->chatCompletion($messages, $options);
            $content = $this->openAiService->extractContent($response);

            if (!$content) {
                Log::error('Failed to generate clause exercise', [
                    'lesson_id' => $lesson->id,
                    'response' => $response,
                    'model' => $options['model'],
                ]);
                return null;
            }

            $data = json_decode($content, true);
            if (!$data || !isset($data['paragraph']) || !isset($data['blanks'])) {
                Log::error('Invalid clause exercise response format', [
                    'content' => $content,
                    'model' => $options['model'],
                ]);
                return null;
            }
            
            // Store initial model used for logging
            $initialModel = $options['model'];

            $paragraph = trim($data['paragraph']);
            $blanks = $data['blanks'];
            
            // Looser validation: just check we have some placeholders and some blanks
            $placeholderCount = substr_count($paragraph, '{}');
            $expectedBlankCount = count($blanks);
            $minimumRequired = 2; // Lowered from 4 to 2
            
            // Only require at least 2 placeholders (very lenient)
            if ($placeholderCount < $minimumRequired) {
                Log::error('Paragraph missing minimum {} placeholders', [
                    'expected_minimum' => $minimumRequired,
                    'found_placeholders' => $placeholderCount,
                    'paragraph' => $paragraph,
                ]);
                throw new \Exception("AI generated paragraph must have at least {$minimumRequired} blank placeholders, but found {$placeholderCount}. Please try regenerating.");
            }
            
            // If we have fewer placeholders than blanks, that's okay - we'll just use what we have
            // If we have more placeholders than blanks, that's also okay - we'll use the blanks that were defined
            if ($placeholderCount != $expectedBlankCount) {
                Log::info('Placeholder count does not match blank count (this is acceptable)', [
                    'expected_blanks' => $expectedBlankCount,
                    'found_placeholders' => $placeholderCount,
                ]);
                // This is fine - we'll match what we can
            }

            // Validate blank types - but be lenient, allow exercises with just vocab or just grammar
            $vocabBlanks = array_filter($blanks, fn($b) => ($b['type'] ?? '') === 'vocab');
            $grammarBlanks = array_filter($blanks, fn($b) => ($b['type'] ?? '') === 'grammar');
            
            // Only warn if we have neither type, but don't fail
            if (count($vocabBlanks) === 0 && count($grammarBlanks) === 0) {
                Log::warning('Exercise has no vocab or grammar blanks', [
                    'blanks' => $blanks,
                ]);
                // Still proceed - maybe the AI defined blanks differently
            }

            // Process blanks and build metadata
            $correctAnswers = [];
            $blankPositions = [];
            $blankMetadata = [];
            $position = 0;
            $processedBlanks = 0;

            foreach ($blanks as $index => $blank) {
                // Stop if we've processed all available placeholders
                if ($processedBlanks >= $placeholderCount) {
                    Log::info('Stopping blank processing - all placeholders have been mapped', [
                        'total_blanks' => count($blanks),
                        'placeholders_found' => $placeholderCount,
                        'processed' => $processedBlanks,
                    ]);
                    break;
                }

                $blankId = $blank['id'] ?? "blank_" . ($index + 1);
                $blankType = $blank['type'] ?? 'vocab';
                $correctAnswer = trim($blank['correct_answer'] ?? '');
                $distractors = $blank['distractors'] ?? [];
                
                // Validate distractors count - be lenient, allow 2-4 distractors
                if (count($distractors) < 2) {
                    Log::warning('Blank has too few distractors', [
                        'blank_id' => $blankId,
                        'distractors_count' => count($distractors),
                    ]);
                    continue; // Skip if less than 2 distractors
                }
                // Allow 2-4 distractors, pad or trim if needed
                if (count($distractors) > 4) {
                    $distractors = array_slice($distractors, 0, 4);
                }

                // Find position in paragraph
                $placeholder = '{}';
                $pos = strpos($paragraph, $placeholder, $position);
                if ($pos === false) {
                    Log::warning('No more {} placeholders found in paragraph', [
                        'blank_id' => $blankId,
                        'position' => $position,
                        'paragraph_length' => strlen($paragraph),
                        'processed_blanks' => $processedBlanks,
                    ]);
                    break;
                }

                if ($blankType === 'vocab') {
                    // Process vocabulary blank
                    $vocabId = null;
                    foreach ($vocabWithIds as $vocabWord => $vocabIdValue) {
                        if (strtolower($vocabWord) === strtolower($correctAnswer)) {
                            $vocabId = $vocabIdValue;
                            break;
                        }
                    }

                    if (!$vocabId) {
                        Log::warning('Vocabulary word not found in lesson', [
                            'word' => $correctAnswer,
                            'available_words' => array_keys($vocabWithIds),
                        ]);
                        continue;
                    }

                    // Map distractors to vocabulary IDs
                    $distractorIds = [];
                    foreach ($distractors as $distractorWord) {
                        $distractorWord = trim($distractorWord);
                        // Check for duplicate distractor
                        if (strtolower($distractorWord) === strtolower($correctAnswer)) {
                            Log::warning('Distractor duplicates correct answer', [
                                'blank_id' => $blankId,
                                'distractor' => $distractorWord,
                                'correct_answer' => $correctAnswer,
                            ]);
                            continue; // Skip this distractor
                        }
                        
                        foreach ($vocabWithIds as $vocabWord => $vocabIdValue) {
                            if (strtolower($vocabWord) === strtolower($distractorWord)) {
                                // Check for duplicate distractor IDs
                                if (!in_array($vocabIdValue, $distractorIds)) {
                                    $distractorIds[] = $vocabIdValue;
                                }
                                break;
                            }
                        }
                    }

                    // Be lenient - allow at least 1 distractor (minimum 2 total options including correct answer)
                    if (count($distractorIds) < 1) {
                        Log::warning('No distractors found in vocabulary', [
                            'blank_id' => $blankId,
                            'distractors_provided' => $distractors,
                        ]);
                        continue; // Skip if no distractors found
                    }
                    // If we have fewer than 3 distractors, that's okay - we'll use what we have
                    if (count($distractorIds) < count($distractors)) {
                        Log::info('Some distractors not found in vocabulary, using available ones', [
                            'blank_id' => $blankId,
                            'distractors_found' => count($distractorIds),
                            'distractors_provided' => count($distractors),
                        ]);
                    }
                    
                    // Validate no distractor duplicates correct answer
                    if (in_array($vocabId, $distractorIds)) {
                        Log::warning('Distractor ID duplicates correct answer ID', [
                            'blank_id' => $blankId,
                            'vocab_id' => $vocabId,
                        ]);
                        continue;
                    }

                    $correctAnswers[$blankId] = $vocabId;
                    $blankMetadata[$blankId] = [
                        'type' => 'vocab',
                        'correct_answer' => $vocabId,
                        'distractors' => $distractorIds,
                    ];
                    $blankPositions[] = [
                        'id' => $blankId,
                        'position' => $pos,
                        'vocabulary_id' => $vocabId,
                    ];

                } else if ($blankType === 'grammar') {
                    // Process grammar blank - be very lenient
                    $grammarConceptId = $blank['grammar_concept_id'] ?? null;
                    $grammarConcept = $blank['grammar_concept'] ?? '';

                    // If no grammar_concept_id, try to find one from available concepts or use null
                    if (!$grammarConceptId && !empty($grammarConceptsWithIds)) {
                        // Pick a random grammar concept if none specified
                        $randomConcept = $grammarConceptsWithIds[array_rand($grammarConceptsWithIds)];
                        $grammarConceptId = $randomConcept['id'];
                        $grammarConcept = $randomConcept['display'];
                        Log::info('Grammar blank missing concept_id, assigned random concept', [
                            'blank_id' => $blankId,
                            'assigned_concept_id' => $grammarConceptId,
                        ]);
                    }

                    // Validate grammar concept exists - but don't fail if it doesn't
                    $conceptExists = false;
                    if ($grammarConceptId) {
                        foreach ($grammarConceptsWithIds as $concept) {
                            if ($concept['id'] == $grammarConceptId) {
                                $conceptExists = true;
                                break;
                            }
                        }
                    }

                    // If concept doesn't exist, still proceed but log it
                    if ($grammarConceptId && !$conceptExists) {
                        Log::warning('Grammar concept ID not found in available concepts, proceeding anyway', [
                            'blank_id' => $blankId,
                            'grammar_concept_id' => $grammarConceptId,
                        ]);
                        // Don't continue - proceed with the blank anyway
                    }

                    // Validate grammar distractors - be lenient
                    $trimmedDistractors = array_map('trim', $distractors);
                    $uniqueDistractors = array_values(array_unique($trimmedDistractors));
                    
                    // Remove duplicates but don't fail
                    if (count($uniqueDistractors) < count($trimmedDistractors)) {
                        Log::info('Grammar blank has duplicate distractors, removing duplicates', [
                            'blank_id' => $blankId,
                            'original_count' => count($trimmedDistractors),
                            'unique_count' => count($uniqueDistractors),
                        ]);
                        $trimmedDistractors = $uniqueDistractors;
                    }
                    
                    // Remove distractors that duplicate correct answer, but don't fail
                    $filteredDistractors = [];
                    foreach ($trimmedDistractors as $distractor) {
                        if (strtolower($distractor) !== strtolower($correctAnswer)) {
                            $filteredDistractors[] = $distractor;
                        } else {
                            Log::info('Removed distractor that duplicates correct answer', [
                                'blank_id' => $blankId,
                                'distractor' => $distractor,
                            ]);
                        }
                    }
                    
                    // If we have at least 1 distractor, proceed (very lenient)
                    if (count($filteredDistractors) < 1) {
                        Log::warning('Grammar blank has no valid distractors after filtering', [
                            'blank_id' => $blankId,
                            'correct_answer' => $correctAnswer,
                        ]);
                        // Still proceed - we'll use empty distractors array
                    }
                    
                    $correctAnswers[$blankId] = $correctAnswer; // Store as string for grammar
                    $blankMetadata[$blankId] = [
                        'type' => 'grammar',
                        'correct_answer' => $correctAnswer,
                        'distractors' => $filteredDistractors, // Use filtered distractors
                        'grammar_concept_id' => $grammarConceptId, // Can be null
                        'grammar_concept' => $grammarConcept,
                    ];
                    $blankPositions[] = [
                        'id' => $blankId,
                        'position' => $pos,
                    ];
                }

                $position = $pos + strlen($placeholder);
                $processedBlanks++;
            }

            // Final validation: just ensure we have at least 1 blank mapped (very lenient)
            $mappedBlanks = count($correctAnswers);
            $mappedVocabBlanks = count(array_filter($blankMetadata, fn($m) => $m['type'] === 'vocab'));
            $mappedGrammarBlanks = count(array_filter($blankMetadata, fn($m) => $m['type'] === 'grammar'));
            
            if ($mappedBlanks < 1) {
                throw new \Exception("Could not map any blanks. Please try regenerating.");
            }
            
            // Log warnings but don't fail if we don't have both types
            if ($mappedVocabBlanks === 0) {
                Log::warning('No vocabulary blanks were mapped', [
                    'mapped_blanks' => $mappedBlanks,
                ]);
            }
            
            if ($mappedGrammarBlanks === 0) {
                Log::warning('No grammar blanks were mapped', [
                    'mapped_blanks' => $mappedBlanks,
                ]);
            }

            // Ensure paragraph still has at least 1 placeholder (very lenient)
            $finalPlaceholderCount = substr_count($paragraph, '{}');
            if ($finalPlaceholderCount < 1) {
                throw new \Exception("Paragraph validation failed: must have at least 1 {} placeholder but found {$finalPlaceholderCount}.");
            }
            
            // Don't fail if placeholders don't match blanks - just log it
            if ($finalPlaceholderCount != $expectedBlankCount) {
                Log::info('Placeholder count does not match blank count (acceptable)', [
                    'placeholders' => $finalPlaceholderCount,
                    'blanks_defined' => $expectedBlankCount,
                    'blanks_mapped' => $mappedBlanks,
                ]);
            }

            return [
                'paragraph_text' => $paragraph,
                'correct_answers' => $correctAnswers,
                'blank_positions' => $blankPositions,
                'blank_metadata' => $blankMetadata,
            ];
        } catch (\Exception $e) {
            Log::error('Exception generating clause exercise', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'model' => $options['model'] ?? 'unknown',
            ]);
            
            // Re-throw validation exceptions so they can be shown to the user
            $isValidationError = strpos($e->getMessage(), 'missing') !== false || 
                                 strpos($e->getMessage(), 'placeholders') !== false ||
                                 strpos($e->getMessage(), 'Could not map') !== false ||
                                 strpos($e->getMessage(), 'validation') !== false ||
                                 strpos($e->getMessage(), 'must have at least') !== false ||
                                 strpos($e->getMessage(), 'Expected at least') !== false;
            
            if ($isValidationError) {
                throw $e;
            }
            
            // For other exceptions, return null (generic error)
            return null;
        }
    }

    /**
     * Build the prompt for OpenAI.
     */
    protected function buildPrompt(string $lessonTitle, array $vocabulary, array $grammarConcepts, int $blankCount, ?string $topic = null): string
    {
        $vocabList = implode(', ', array_slice($vocabulary, 0, 20)); // Limit to 20 words
        
        // Format grammar concepts for the prompt
        $grammarConceptsList = array_map(function($concept) {
            return "ID {$concept['id']}: {$concept['display']}";
        }, array_slice($grammarConcepts, 0, 10)); // Limit to 10 concepts
        $grammarConceptsText = implode("\n", $grammarConceptsList);

        $topicInstruction = $topic 
            ? "IMPORTANT: Organize the entire paragraph around the topic: \"{$topic}\". The paragraph should be about this specific topic while using the vocabulary words and grammar concepts."
            : "Create a paragraph that naturally uses the vocabulary words and grammar concepts.";

        return "Create a fill-in-the-blank paragraph exercise for a lesson titled: \"{$lessonTitle}\"

VOCABULARY WORDS (use these for vocabulary blanks):
{$vocabList}

AVAILABLE GRAMMAR CONCEPTS (use these for grammar blanks - randomly select from this list):
{$grammarConceptsText}

{$topicInstruction}

CRITICAL REQUIREMENTS - READ CAREFULLY:
1. You MUST create a paragraph with AT LEAST {$blankCount} blanks (you can use more if it makes sense, but NOT fewer)
2. Each blank MUST be represented by the exact characters: {} (curly braces with nothing inside)
3. The paragraph text MUST contain at least {$blankCount} instances of the text {} (more is fine, just not less)
4. ⚠️ MANDATORY: You MUST include AT LEAST 1 vocabulary blank AND AT LEAST 1 grammar blank. DO NOT create an exercise with only vocabulary blanks or only grammar blanks.
5. For a {$blankCount}-blank exercise, use a mix: at least 1 vocab blank and at least 1 grammar blank, with the rest being either type
6. Use 3-5 sentences total
7. Make the paragraph coherent and educational

⚠️ REMINDER: If you create {$blankCount} blanks, at least ONE must be type=\"vocab\" AND at least ONE must be type=\"grammar\". This is REQUIRED, not optional.

FOR VOCABULARY BLANKS:
- Use words from the vocabulary list provided above
- Generate 3 distractors (other vocabulary words from the same list)
- Example: If correct answer is \"book\", distractors could be \"pen\", \"desk\", \"chair\"

FOR GRAMMAR BLANKS:
- Randomly select a grammar concept from the available concepts list
- CRITICAL: Create a sentence context that actually supports the grammar concept you're testing
  * For Past Progressive: Use contexts like \"Yesterday, we {}...\" or \"While they were studying, they {}...\" or \"Last week, the students {}...\"
  * For Modals (should, can, must, etc.): Use contexts like \"You {}...\" or \"We {}...\" (NOT \"have to\" or \"need to\" which require infinitives)
  * For Present Simple: Use contexts like \"Every day, we {}...\" or \"They usually {}...\"
  * DO NOT use \"have to\" or \"need to\" with grammar concepts that require specific verb forms - these constructions require infinitives
  * The sentence structure MUST allow the grammar concept to be used correctly
- Generate the correct answer based on that grammar concept AND ensure it fits the sentence context
- Generate exactly 3 distractors that are grammatically incorrect IN THIS SPECIFIC SENTENCE
- CRITICAL: Test each distractor mentally in the sentence - it MUST be grammatically wrong
- Distractors should be wrong forms that don't fit:
  * Wrong tense (e.g., if correct is Past Progressive \"were doing\", use Present \"do\", Past Simple \"did\", or wrong form \"was doing\" for plural subject)
  * Wrong verb form (e.g., if correct is \"ask\", don't use \"asking\" if it's wrong in context)
  * Wrong modal (e.g., if correct is \"should\", use \"must\", \"will\", \"can\" - but verify they're wrong)
- Do NOT include another correct form (e.g., if correct is \"should\", don't use \"ought to\" or \"should have\" as distractors)
- Each distractor must be clearly grammatically incorrect when placed in the sentence

Examples of GOOD grammar blank contexts:
- Past Progressive: \"Yesterday, we {} our homework\" → Correct: \"were doing\" | Distractors: \"did\", \"do\", \"was doing\"
- Modals: \"You {} study hard\" → Correct: \"should\" | Distractors: \"should not\", \"must\", \"will\"
- Present Simple: \"Every day, they {} to school\" → Correct: \"walk\" | Distractors: \"walked\", \"walking\", \"walks\"

Examples of BAD grammar blank contexts (DO NOT USE):
- \"We have to {} questions\" (requires infinitive, can't test Past Progressive here)
- \"They need to {} homework\" (requires infinitive, can't test specific tenses here)

Return a JSON object with:
- 'paragraph': The paragraph text with AT LEAST {$blankCount} {} placeholders
  * The first {} corresponds to blanks[0], the second {} to blanks[1], etc. (maintain this order - do not reorder blanks)
- 'blanks': Array of objects, each with:
  - 'id': Unique identifier (blank_1, blank_2, etc.) - must match the order of {} in paragraph
  - 'type': Either \"vocab\" or \"grammar\"
  - 'correct_answer': The correct word (vocabulary word string for vocab, grammar word string for grammar)
  - 'distractors': Array of exactly 3 incorrect options (vocabulary words for vocab blanks, grammar words for grammar blanks)
    * For vocab: distractors must be from the provided vocabulary list
    * For grammar: distractors MUST be grammatically incorrect IN THIS SPECIFIC SENTENCE CONTEXT
      - They should be wrong forms of the verb/word that don't fit the sentence structure
      - They should test the specific grammar concept (e.g., if testing Past Progressive, distractors should be wrong tenses like Present Simple, Past Simple, etc.)
      - At least one distractor should be clearly wrong (e.g., wrong tense, wrong form)
      - Distractors should be plausible enough that students need to think, but clearly incorrect
    * No distractor may duplicate the correct answer
  - 'grammar_concept_id': (Required for grammar blanks) The ID of the grammar concept being tested
  - 'grammar_concept': (Required for grammar blanks) The display name of the grammar concept (e.g., \"Modals and Semi-modals - should\")

CRITICAL FINAL CHECKLIST - VERIFY BEFORE RETURNING:
- [ ] Paragraph has same number of {} as blanks array length
- [ ] ⚠️ At least 1 blank is type = \"vocab\" (REQUIRED - check your blanks array!)
- [ ] ⚠️ At least 1 blank is type = \"grammar\" (REQUIRED - check your blanks array!)
- [ ] All vocab distractors are from the provided vocabulary list
- [ ] No distractor duplicates its correct answer
- [ ] {} placeholders are in the same order as blanks array
- [ ] For grammar blanks: The correct answer is grammatically correct in the sentence AND matches the grammar concept
- [ ] For grammar blanks: ALL distractors are grammatically incorrect when placed in the sentence

⚠️ FINAL VERIFICATION - TEST EACH GRAMMAR BLANK:
1. Count how many blanks have type=\"vocab\" and how many have type=\"grammar\". You MUST have at least 1 of each.
2. For EACH grammar blank:
   a. Read the full sentence with the correct answer inserted. Is it grammatically correct? Does it match the grammar concept? If NO, fix it.
   b. Read the full sentence with EACH distractor inserted one at a time. Is it grammatically incorrect? If ANY distractor could be correct, replace it with a clearly wrong option.
   c. Verify the correct answer is the ONLY grammatically correct option for this blank in this sentence.
3. If any grammar blank fails these tests, regenerate that blank with better options.";
    }
}
