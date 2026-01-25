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
     * Extract all {{blank_id}} tokens from paragraph text
     * Only matches {{blank_\d+}} format
     */
    protected function extractBlankTokens(string $paragraph): array
    {
        preg_match_all('/\{\{(blank_\d+)\}\}/', $paragraph, $matches);
        return $matches[1] ?? []; // Returns ['blank_1', 'blank_2', ...]
    }

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
                $vocabWord = is_array($vocab) ? ($vocab['word'] ?? $vocab['english_word'] ?? '') : (string)$vocab;
                return strtolower($vocabWord) !== strtolower($correctAnswer);
            });
            
            if (count($availableVocab) < 3) {
                // Not enough vocab, use generic wrong words
                return ['different', 'wrong', 'incorrect'];
            }
            
            // Fix array_rand bug: use array_values() after filtering to ensure sequential indices
            $availableVocab = array_values($availableVocab);
            $selected = array_rand($availableVocab, min(3, count($availableVocab)));
            $selected = is_array($selected) ? $selected : [$selected];
            
            return array_map(function($idx) use ($availableVocab) {
                $vocab = $availableVocab[$idx];
                return is_array($vocab) ? ($vocab['word'] ?? $vocab['english_word'] ?? '') : (string)$vocab;
            }, $selected);
            
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
                $base = str_replace('ing', '', $correct);
                return [$base, str_replace('ing', 'ed', $correct), $correct . 's'];
            } elseif (preg_match('/ed$/', $correct)) {
                // -ed form -> wrong forms
                $base = str_replace('ed', '', $correct);
                return [$base, str_replace('ed', 'ing', $correct), $correct . 's'];
            }
            
            // Generic fallback
            return ['wrong', 'incorrect', 'different'];
        }
    }

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
        
        // Check token/blank consistency - DO NOT repair, fail instead
        $missingTokens = array_diff($tokens, $blankIds);
        $orphanBlanks = array_diff($blankIds, $tokens);
        
        if (!empty($missingTokens)) {
            $errors[] = "Paragraph contains tokens without blanks: " . implode(', ', $missingTokens) . ". Regenerate Step 2.";
        }
        
        if (!empty($orphanBlanks)) {
            $errors[] = "Blanks exist without tokens: " . implode(', ', $orphanBlanks) . ". Regenerate Step 2.";
        }
        
        // Validate each blank
        foreach ($blanks as $blankId => $blank) {
            $blankErrors = [];
            
            // Check type
            if (!in_array($blank['type'] ?? '', ['vocab', 'grammar'])) {
                $blankErrors[] = "Invalid type";
            }
            
            // CRITICAL: Check correct answer exists and is non-empty
            $correctText = trim($blank['correct']['text'] ?? '');
            if (empty($correctText)) {
                $blankErrors[] = "CRITICAL: Missing correct answer text - blank has no correct answer";
            }
            
            // Check distractors count
            $distractors = $blank['distractors'] ?? [];
            if (count($distractors) !== 3) {
                $blankErrors[] = "Expected 3 distractors, found " . count($distractors);
            }
            
            // CRITICAL: Check for duplicates AND ensure correct answer is present
            $correctText = trim($blank['correct']['text'] ?? '');
            $allOptions = array_merge(
                [$correctText],
                array_column($distractors, 'text')
            );
            
            // Must have exactly 4 options (1 correct + 3 distractors)
            if (count($allOptions) !== 4) {
                $blankErrors[] = "CRITICAL: Must have exactly 4 options (1 correct + 3 distractors), found " . count($allOptions);
            }
            
            // Correct answer must be in the options
            if (!in_array($correctText, $allOptions)) {
                $blankErrors[] = "CRITICAL: Correct answer '{$correctText}' is not in options list";
            }
            
            // Check for duplicates
            if (count($allOptions) !== count(array_unique($allOptions))) {
                $blankErrors[] = "Duplicate options found";
            }
            
            // Vocab-specific checks
            if ($blank['type'] === 'vocab') {
                if (empty($blank['correct']['text'])) {
                    $blankErrors[] = "Vocab blank missing correct text";
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

        try {
            // Step 1: Generate a complete paragraph WITHOUT blanks first
            $paragraphPrompt = $this->buildCompleteParagraphPrompt($lesson->title, $vocabList, $grammarConceptsWithIds, $topic);

            $paragraphMessages = [
                [
                    'role' => 'system',
                    'content' => 'You are an educational content creator for English language learners. Generate a coherent, grammatically correct paragraph (3-5 sentences) that naturally uses vocabulary words and grammar concepts. The paragraph should be complete with all words filled in - NO blanks or placeholders.',
                ],
                [
                    'role' => 'user',
                    'content' => $paragraphPrompt,
                ],
            ];

            $paragraphOptions = [
                'model' => $model ?? config('services.openai.translation_model', 'gpt-4o-mini'),
                'temperature' => 0.7,
                'response_format' => [
                    'type' => 'json_schema',
                    'json_schema' => [
                        'name' => 'complete_paragraph',
                        'schema' => [
                            'type' => 'object',
                            'required' => ['paragraph', 'vocabulary_used', 'grammar_concepts_used'],
                            'properties' => [
                                'paragraph' => [
                                    'type' => 'string',
                                    'description' => 'A complete, grammatically correct paragraph (3-5 sentences) with all words filled in. NO blanks or placeholders.',
                                ],
                                'vocabulary_used' => [
                                    'type' => 'array',
                                    'items' => ['type' => 'string'],
                                    'description' => 'List of vocabulary words from the provided list that are used in the paragraph',
                                ],
                                'grammar_concepts_used' => [
                                    'type' => 'array',
                                    'items' => [
                                        'type' => 'object',
                                        'required' => ['id', 'display'],
                                        'properties' => [
                                            'id' => ['type' => 'integer'],
                                            'display' => ['type' => 'string'],
                                        ],
                                    ],
                                    'description' => 'List of grammar concepts from the provided list that are demonstrated in the paragraph',
                                ],
                            ],
                        ],
                    ],
                ],
            ];

            $paragraphResponse = $this->openAiService->chatCompletion($paragraphMessages, $paragraphOptions);
            $paragraphContent = $this->openAiService->extractContent($paragraphResponse);

            if (!$paragraphContent) {
                Log::error('Failed to generate complete paragraph', [
                    'lesson_id' => $lesson->id,
                    'response' => $paragraphResponse,
                ]);
                return null;
            }

            $paragraphData = json_decode($paragraphContent, true);
            if (!$paragraphData || !isset($paragraphData['paragraph'])) {
                Log::error('Invalid paragraph response format', [
                    'content' => $paragraphContent,
                ]);
                return null;
            }

            $completeParagraph = trim($paragraphData['paragraph']);
            $vocabularyUsed = $paragraphData['vocabulary_used'] ?? [];
            $grammarConceptsUsed = $paragraphData['grammar_concepts_used'] ?? [];

            // Step 2: Analyze the paragraph and add blanks strategically
            $addBlanksPrompt = $this->buildAddBlanksPrompt($completeParagraph, $vocabList, $grammarConceptsWithIds, $vocabularyUsed, $grammarConceptsUsed, $blankCount);

            $addBlanksMessages = [
                [
                    'role' => 'system',
                    'content' => 'You are an educational content creator. Analyze a complete paragraph and strategically add fill-in-the-blank exercises. Extract the actual words from the paragraph as correct answers. You must use explicit blank identifiers in the format {{blank_1}}, {{blank_2}}, etc. Each blank must have a unique identifier that matches between the paragraph and the blanks array. ⚠️ CRITICAL: You MUST create EXACTLY ' . $blankCount . ' blanks. The paragraph MUST contain EXACTLY ' . $blankCount . ' tokens, and the blanks array MUST contain EXACTLY ' . $blankCount . ' items. Count them before returning your response.',
                ],
                [
                    'role' => 'user',
                    'content' => $addBlanksPrompt,
                ],
            ];

            $addBlanksOptions = [
                'model' => $model ?? config('services.openai.translation_model', 'gpt-4o-mini'),
                'temperature' => 0.5,
                'response_format' => [
                    'type' => 'json_schema',
                    'json_schema' => [
                        'name' => 'paragraph_with_blanks',
                        'schema' => [
                            'type' => 'object',
                            'required' => ['paragraph', 'blanks'],
                            'properties' => [
                                'paragraph' => [
                                    'type' => 'string',
                                    'description' => 'The paragraph with {{blank_1}}, {{blank_2}}, etc. tokens. MUST contain EXACTLY ' . $blankCount . ' tokens. Count them before returning. If blankCount is ' . $blankCount . ', you MUST have ' . $blankCount . ' tokens.',
                                ],
                                'blanks' => [
                                    'type' => 'array',
                                    'items' => [
                                        'type' => 'object',
                                        'required' => ['blank_id', 'type', 'correct_answer', 'sentence_context'],
                                        'properties' => [
                                            'blank_id' => [
                                                'type' => 'string',
                                                'pattern' => '^blank_\\d+$',
                                                'description' => 'Unique identifier like "blank_1", "blank_2", etc. Must match tokens in paragraph.',
                                            ],
                                            'type' => [
                                                'type' => 'string',
                                                'enum' => ['vocab', 'grammar'],
                                                'description' => 'Type of blank: vocab (uses vocabulary word) or grammar (tests grammar concept)',
                                            ],
                                            'correct_answer' => [
                                                'type' => 'string',
                                                'description' => 'The actual word/phrase from the original paragraph',
                                            ],
                                            'correct_vocab_text' => [
                                                'type' => 'string',
                                                'description' => 'For vocab blanks: the word text (must equal correct_answer)',
                                            ],
                                            'correct_vocab_id' => [
                                                'type' => 'integer',
                                                'description' => 'For vocab blanks: optional vocabulary ID',
                                            ],
                                            'sentence_context' => [
                                                'type' => 'string',
                                                'description' => 'The full sentence containing {{blank_X}} token',
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
                                    'minItems' => $blankCount,
                                    'maxItems' => $blankCount,
                                    'description' => 'Array of EXACTLY ' . $blankCount . ' blank objects. Count them before returning. If blankCount is ' . $blankCount . ', you MUST return ' . $blankCount . ' items.',
                                ],
                            ],
                        ],
                    ],
                ],
            ];

            $addBlanksResponse = $this->openAiService->chatCompletion($addBlanksMessages, $addBlanksOptions);
            $addBlanksContent = $this->openAiService->extractContent($addBlanksResponse);

            if (!$addBlanksContent) {
                Log::error('Failed to add blanks to paragraph', [
                    'lesson_id' => $lesson->id,
                ]);
                return null;
            }

            $blanksData = json_decode($addBlanksContent, true);
            if (!$blanksData || !isset($blanksData['paragraph']) || !isset($blanksData['blanks'])) {
                Log::error('Invalid add blanks response format', [
                    'content' => $addBlanksContent,
                ]);
                return null;
            }

            $paragraph = trim($blanksData['paragraph']);
            $blankInfoArray = $blanksData['blanks'];

            // Validate paragraph has correct number of tokens
            $tokens = $this->extractBlankTokens($paragraph);
            $tokenCount = count($tokens);
            
            // Retry Step 2 if token count is wrong (max 2 retries)
            $step2Retries = 0;
            $maxStep2Retries = 2;
            
            while ($tokenCount !== $blankCount && $step2Retries < $maxStep2Retries) {
                Log::warning('Step 2 generated wrong token count, retrying', [
                    'expected' => $blankCount,
                    'found' => $tokenCount,
                    'retry' => $step2Retries + 1,
                ]);
                
                // Retry Step 2
                $addBlanksResponse = $this->openAiService->chatCompletion($addBlanksMessages, $addBlanksOptions);
                $addBlanksContent = $this->openAiService->extractContent($addBlanksResponse);
                
                if (!$addBlanksContent) {
                    break; // Exit retry loop if response is invalid
                }
                
                $blanksData = json_decode($addBlanksContent, true);
                if (!$blanksData || !isset($blanksData['paragraph']) || !isset($blanksData['blanks'])) {
                    break; // Exit retry loop if format is invalid
                }
                
                $paragraph = trim($blanksData['paragraph']);
                $blankInfoArray = $blanksData['blanks'];
                $tokens = $this->extractBlankTokens($paragraph);
                $tokenCount = count($tokens);
                $step2Retries++;
            }
            
            // Final validation - fail hard if still wrong
            if ($tokenCount !== $blankCount) {
                throw new \Exception("Paragraph must have exactly {$blankCount} {{blank_id}} tokens, but found {$tokenCount} after {$maxStep2Retries} retries. The AI is not following instructions. Please try regenerating the exercise.");
            }

            // Validate blank_ids match tokens
            $blankIdsFromArray = array_column($blankInfoArray, 'blank_id');
            $missingIds = array_diff($tokens, $blankIdsFromArray);
            $extraIds = array_diff($blankIdsFromArray, $tokens);
            if (!empty($missingIds) || !empty($extraIds)) {
                throw new \Exception("Blank IDs don't match tokens. Missing: " . implode(', ', $missingIds) . ". Extra: " . implode(', ', $extraIds));
            }

            // Step 3: Generate distractors for each blank based on the actual sentence context
            // NO continue paths - abort and regenerate Step 2 if any blank is invalid
            $finalBlanks = [];
            foreach ($blankInfoArray as $blankInfo) {
                $blankId = $blankInfo['blank_id'] ?? null;
                if (!$blankId) {
                    Log::error('Blank missing blank_id', ['blank_info' => $blankInfo]);
                    throw new \Exception("Step 2 failed: Blank missing blank_id. Regenerating Step 2.");
                }
                
                $blankType = $blankInfo['type'] ?? 'vocab';
                $correctAnswer = trim($blankInfo['correct_answer'] ?? '');
                $sentenceContext = $blankInfo['sentence_context'] ?? '';
                $grammarConceptId = $blankInfo['grammar_concept_id'] ?? null;
                $grammarConcept = $blankInfo['grammar_concept'] ?? '';

                if (empty($correctAnswer)) {
                    Log::error('Blank missing correct answer', ['blank_id' => $blankId]);
                    throw new \Exception("Step 2 failed: Blank {$blankId} missing correct_answer. Regenerating Step 2.");
                }

                // Generate distractors for this specific blank
                $distractorsPrompt = $this->buildDistractorsPrompt(
                    $blankId,
                    $blankType,
                    $sentenceContext,
                    $correctAnswer,
                    $vocabList,
                    $grammarConceptsWithIds,
                    $grammarConceptId,
                    $grammarConcept
                );

                $systemMessage = $blankType === 'vocab'
                    ? 'You are an educational content creator. Generate appropriate distractors for a vocabulary fill-in-the-blank exercise. The correct answer is already known from the sentence. Generate distractors that are grammatically incorrect or contextually wrong when placed in the sentence. All distractors must come from the provided vocabulary list.'
                    : 'You are an educational content creator. Generate appropriate distractors for a grammar fill-in-the-blank exercise. The correct answer is already known from the sentence. Generate distractors that are grammatically incorrect when placed in the sentence. The correct answer must be the ONLY grammatically correct option.';

                $distractorsMessages = [
                    [
                        'role' => 'system',
                        'content' => $systemMessage,
                    ],
                    [
                        'role' => 'user',
                        'content' => $distractorsPrompt,
                    ],
                ];

                $distractorsResponse = $this->openAiService->chatCompletion($distractorsMessages, [
                    'model' => $model ?? config('services.openai.translation_model', 'gpt-4o-mini'),
                    'temperature' => 0.5,
                    'response_format' => [
                        'type' => 'json_schema',
                        'json_schema' => [
                            'name' => 'distractors',
                            'schema' => [
                                'type' => 'object',
                                'required' => ['distractors'],
                                'properties' => [
                                    'distractors' => [
                                        'type' => 'array',
                                        'items' => ['type' => 'string'],
                                        'minItems' => 3,
                                        'maxItems' => 3,
                                        'description' => 'Exactly 3 incorrect options that are grammatically wrong or contextually incorrect when placed in the sentence',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ]);

                // Try AI generation
                $distractors = null;
                try {
                    $distractorsContent = $this->openAiService->extractContent($distractorsResponse);
                    if ($distractorsContent) {
                        $distractorsData = json_decode($distractorsContent, true);
                        if (isset($distractorsData['distractors']) && count($distractorsData['distractors']) === 3) {
                            $distractors = array_map('trim', $distractorsData['distractors']);
                        }
                    }
                } catch (\Exception $e) {
                    Log::warning('AI distractor generation failed', [
                        'blank_id' => $blankId,
                        'error' => $e->getMessage()
                    ]);
                }
                
                // Fallback if AI failed
                if (!$distractors || count($distractors) !== 3) {
                    if ($blankType === 'grammar') {
                        // For grammar: retry AI once, then regenerate exercise if still failing
                        Log::info('Grammar distractor generation failed, retrying AI once', ['blank_id' => $blankId]);
                        try {
                            $retryResponse = $this->openAiService->chatCompletion($distractorsMessages, [
                                'model' => $model ?? config('services.openai.translation_model', 'gpt-4o-mini'),
                                'temperature' => 0.5,
                                'response_format' => [
                                    'type' => 'json_schema',
                                    'json_schema' => [
                                        'name' => 'distractors',
                                        'schema' => [
                                            'type' => 'object',
                                            'required' => ['distractors'],
                                            'properties' => [
                                                'distractors' => [
                                                    'type' => 'array',
                                                    'items' => ['type' => 'string'],
                                                    'minItems' => 3,
                                                    'maxItems' => 3,
                                                    'description' => 'Exactly 3 incorrect options that are grammatically wrong or contextually incorrect when placed in the sentence',
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ]);
                            
                            $retryContent = $this->openAiService->extractContent($retryResponse);
                            if ($retryContent) {
                                $retryData = json_decode($retryContent, true);
                                if (isset($retryData['distractors']) && count($retryData['distractors']) === 3) {
                                    $distractors = array_map('trim', $retryData['distractors']);
                                }
                            }
                        } catch (\Exception $e) {
                            Log::warning('Grammar distractor retry failed', [
                                'blank_id' => $blankId,
                                'error' => $e->getMessage()
                            ]);
                        }
                        
                        // If retry also failed, regenerate entire exercise
                        if (!$distractors || count($distractors) !== 3) {
                            throw new \Exception("Step 3 failed: Could not generate grammar distractors for blank {$blankId} after retry. Regenerating exercise.");
                        }
                    } else {
                        // For vocab: use fallback
                        Log::info('Using fallback distractors', ['blank_id' => $blankId]);
                        $distractors = $this->generateFallbackDistractors(
                            $blankType,
                            $correctAnswer,
                            $vocabulary->toArray(),
                            $grammarConceptId,
                            $grammarConcept
                        );
                    }
                }
                
                // Validate correct answer is not empty
                if (empty(trim($correctAnswer))) {
                    Log::error('Blank has empty correct answer after processing', [
                        'blank_id' => $blankId,
                        'blank_info' => $blankInfo
                    ]);
                    throw new \Exception("Step 2 failed: Blank {$blankId} has empty correct_answer. Regenerating Step 2.");
                }
                
                // Validate we have exactly 3 distractors
                if (count($distractors) !== 3) {
                    Log::error('Blank has incorrect distractor count', [
                        'blank_id' => $blankId,
                        'distractor_count' => count($distractors)
                    ]);
                    throw new \Exception("Step 3 failed: Blank {$blankId} must have exactly 3 distractors, found " . count($distractors) . ". Regenerating exercise.");
                }
                
                // Build blank entry
                $blankEntry = [
                    'type' => $blankType,
                    'correct' => ['text' => trim($correctAnswer)], // Ensure trimmed
                    'distractors' => array_map(function($dist) {
                        return ['text' => trim($dist)]; // Ensure trimmed
                    }, $distractors),
                    'sentence_context' => $sentenceContext,
                ];
                
                // Final validation: ensure correct answer is in the options
                $allOptionTexts = array_merge(
                    [$blankEntry['correct']['text']],
                    array_column($blankEntry['distractors'], 'text')
                );
                
                if (empty($blankEntry['correct']['text']) || !in_array($blankEntry['correct']['text'], $allOptionTexts)) {
                    throw new \Exception("Step 3 failed: Blank {$blankId} correct answer is missing or invalid. Regenerating exercise.");
                }
                
                // Add vocab-specific data
                if ($blankType === 'vocab') {
                    // Use correct_vocab_text if provided and non-empty, otherwise use correct_answer
                    $correctVocabText = !empty(trim($blankInfo['correct_vocab_text'] ?? '')) 
                        ? trim($blankInfo['correct_vocab_text']) 
                        : trim($correctAnswer);
                    
                    // Validate: correct_vocab_text should equal correct_answer (per spec)
                    if (!empty($blankInfo['correct_vocab_text']) && 
                        strtolower(trim($blankInfo['correct_vocab_text'])) !== strtolower(trim($correctAnswer))) {
                        Log::warning('Vocab text mismatch, using correct_answer', [
                            'blank_id' => $blankId,
                            'correct_answer' => $correctAnswer,
                            'correct_vocab_text' => $blankInfo['correct_vocab_text']
                        ]);
                        $correctVocabText = trim($correctAnswer);
                    }
                    
                    // Ensure we don't overwrite with empty value
                    if (!empty($correctVocabText)) {
                        $blankEntry['correct']['text'] = $correctVocabText;
                    } else {
                        // Fallback: use correct_answer if vocab_text is empty
                        $blankEntry['correct']['text'] = trim($correctAnswer);
                    }
                    
                    $blankEntry['correct']['vocab_id'] = $blankInfo['correct_vocab_id'] ?? null;
                    
                    // Match distractors to vocab IDs
                    foreach ($blankEntry['distractors'] as $idx => $dist) {
                        $vocabMatch = $vocabulary->first(function($vocab) use ($dist) {
                            return strtolower($vocab->english_word) === strtolower($dist['text']);
                        });
                        if ($vocabMatch) {
                            $blankEntry['distractors'][$idx]['vocab_id'] = $vocabMatch->id;
                        }
                    }
                }
                
                // Add grammar-specific data
                if ($blankType === 'grammar') {
                    $blankEntry['grammar_concept_id'] = $grammarConceptId;
                    $blankEntry['grammar_concept'] = $grammarConcept;
                }
                
                // CRITICAL FINAL VALIDATION: Ensure correct answer is ALWAYS present and non-empty
                if (empty($blankEntry['correct']['text'] ?? '')) {
                    Log::error('CRITICAL: Blank entry missing correct answer after all processing', [
                        'blank_id' => $blankId,
                        'blank_type' => $blankType,
                        'correct_answer_original' => $correctAnswer,
                        'blank_entry' => $blankEntry
                    ]);
                    throw new \Exception("CRITICAL ERROR: Blank {$blankId} is missing correct answer. This should never happen. Regenerating exercise.");
                }
                
                // Ensure correct answer is in the final options list
                $finalAllOptions = array_merge(
                    [$blankEntry['correct']['text']],
                    array_column($blankEntry['distractors'] ?? [], 'text')
                );
                
                if (!in_array($blankEntry['correct']['text'], $finalAllOptions)) {
                    Log::error('CRITICAL: Correct answer not found in options', [
                        'blank_id' => $blankId,
                        'correct_text' => $blankEntry['correct']['text'],
                        'all_options' => $finalAllOptions
                    ]);
                    throw new \Exception("CRITICAL ERROR: Blank {$blankId} correct answer is not in options list. Regenerating exercise.");
                }
                
                // Ensure we have exactly 4 options total (1 correct + 3 distractors)
                $totalOptions = count($finalAllOptions);
                if ($totalOptions !== 4) {
                    Log::error('CRITICAL: Wrong total option count', [
                        'blank_id' => $blankId,
                        'expected' => 4,
                        'found' => $totalOptions,
                        'correct' => $blankEntry['correct']['text'],
                        'distractors' => array_column($blankEntry['distractors'] ?? [], 'text')
                    ]);
                    throw new \Exception("CRITICAL ERROR: Blank {$blankId} must have exactly 4 options (1 correct + 3 distractors), found {$totalOptions}. Regenerating exercise.");
                }
                
                $finalBlanks[$blankId] = $blankEntry;
            }
            
            // CRITICAL PRE-VALIDATION: Ensure every blank has a correct answer before final validation
            foreach ($finalBlanks as $blankId => $blank) {
                if (empty($blank['correct']['text'] ?? '')) {
                    throw new \Exception("CRITICAL: Blank {$blankId} missing correct answer before final validation. Regenerating exercise.");
                }
                
                $allOpts = array_merge(
                    [$blank['correct']['text']],
                    array_column($blank['distractors'] ?? [], 'text')
                );
                
                if (count($allOpts) !== 4) {
                    throw new \Exception("CRITICAL: Blank {$blankId} must have exactly 4 options (1 correct + 3 distractors), found " . count($allOpts) . ". Regenerating exercise.");
                }
                
                if (!in_array($blank['correct']['text'], $allOpts)) {
                    throw new \Exception("CRITICAL: Blank {$blankId} correct answer not found in options. Regenerating exercise.");
                }
            }
            
            // Validate - NO repairs, fail if invalid
            $validation = $this->validateAndRepairExercise(
                $paragraph,
                $finalBlanks,
                $vocabulary->toArray(),
                $blankCount
            );
            
            if (!$validation['valid']) {
                throw new \Exception("Exercise validation failed: " . implode('; ', $validation['errors']) . ". Regenerate exercise.");
            }
            
            // FINAL CRITICAL CHECK: After validation, ensure correct answers are still present
            foreach ($validation['blanks'] as $blankId => $blank) {
                if (empty($blank['correct']['text'] ?? '')) {
                    throw new \Exception("CRITICAL: Blank {$blankId} lost correct answer during validation. Regenerating exercise.");
                }
                
                $finalOpts = array_merge(
                    [$blank['correct']['text']],
                    array_column($blank['distractors'] ?? [], 'text')
                );
                
                if (!in_array($blank['correct']['text'], $finalOpts) || count($finalOpts) !== 4) {
                    throw new \Exception("CRITICAL: Blank {$blankId} invalid after validation. Regenerating exercise.");
                }
            }
            
            $paragraph = $validation['paragraph'];
            $finalBlanks = $validation['blanks'];
            
            // STEP 4: Self-check - Fill in correct answers and validate the completed paragraph
            $completedParagraph = $this->fillBlanksWithCorrectAnswers($paragraph, $finalBlanks);
            $selfCheckResult = $this->validateCompletedParagraph($completedParagraph, $lesson->title, $model);
            
            if (!$selfCheckResult['valid']) {
                Log::warning('Self-check failed: Completed paragraph does not make sense', [
                    'errors' => $selfCheckResult['errors'],
                    'completed_paragraph' => $completedParagraph
                ]);
                throw new \Exception("Self-check failed: The exercise doesn't make sense when completed. " . implode('; ', $selfCheckResult['errors']) . ". Regenerating exercise.");
            }

            // Return new format with blanks as single source of truth
            return [
                'paragraph_text' => $paragraph,
                'blanks' => $finalBlanks,
            ];
        } catch (\Exception $e) {
            Log::error('Exception generating clause exercise', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'model' => $model ?? 'unknown',
            ]);
            
            // Re-throw validation exceptions so they can be shown to the user
            $isValidationError = strpos($e->getMessage(), 'missing') !== false || 
                                 strpos($e->getMessage(), 'placeholders') !== false ||
                                 strpos($e->getMessage(), 'Could not map') !== false ||
                                 strpos($e->getMessage(), 'validation') !== false ||
                                 strpos($e->getMessage(), 'must have at least') !== false ||
                                 strpos($e->getMessage(), 'Expected at least') !== false ||
                                 strpos($e->getMessage(), 'tokens') !== false ||
                                 strpos($e->getMessage(), 'Blank IDs') !== false ||
                                 strpos($e->getMessage(), 'Exercise validation') !== false;
            
            if ($isValidationError) {
                throw $e;
            }
            
            // For other exceptions, return null (generic error)
            return null;
        }
    }

    /**
     * Build the prompt for generating a complete paragraph WITHOUT blanks.
     */
    protected function buildCompleteParagraphPrompt(string $lessonTitle, array $vocabulary, array $grammarConcepts, ?string $topic = null): string
    {
        $vocabList = implode(', ', array_slice($vocabulary, 0, 20));
        
        $grammarConceptsList = array_map(function($concept) {
            return "ID {$concept['id']}: {$concept['display']}";
        }, array_slice($grammarConcepts, 0, 10));
        $grammarConceptsText = implode("\n", $grammarConceptsList);

        $topicInstruction = $topic 
            ? "IMPORTANT: Organize the entire paragraph around the topic: \"{$topic}\"."
            : "Create a paragraph that naturally uses the vocabulary words and grammar concepts.";

        return "Create a complete, grammatically correct paragraph for a lesson titled: \"{$lessonTitle}\"

VOCABULARY WORDS (use some of these in the paragraph):
{$vocabList}

AVAILABLE GRAMMAR CONCEPTS (use some of these in the paragraph):
{$grammarConceptsText}

{$topicInstruction}

REQUIREMENTS:
1. Create a coherent paragraph (3-5 sentences) that is COMPLETE with all words filled in
2. NO blanks, NO placeholders, NO {} - the paragraph must be fully written
3. Use at least 4-8 vocabulary words from the provided list
4. Demonstrate at least 2-3 grammar concepts from the provided list
5. Make the paragraph educational and contextually appropriate
6. Ensure all sentences are grammatically correct and flow naturally

Return JSON with:
- paragraph: The complete paragraph text (NO blanks or placeholders)
- vocabulary_used: Array of vocabulary words from the provided list that appear in the paragraph
- grammar_concepts_used: Array of grammar concepts (with id and display) that are demonstrated in the paragraph";
    }

    /**
     * Build the prompt for adding blanks to a complete paragraph.
     */
    protected function buildAddBlanksPrompt(string $completeParagraph, array $vocabulary, array $grammarConcepts, array $vocabularyUsed, array $grammarConceptsUsed, int $blankCount): string
    {
        $vocabList = implode(', ', array_slice($vocabulary, 0, 20));
        
        $grammarConceptsList = array_map(function($concept) {
            return "ID {$concept['id']}: {$concept['display']}";
        }, array_slice($grammarConcepts, 0, 10));
        $grammarConceptsText = implode("\n", $grammarConceptsList);

        $vocabUsedText = implode(', ', array_slice($vocabularyUsed, 0, 10));
        $grammarUsedText = implode(', ', array_map(function($gc) {
            return $gc['display'] ?? '';
        }, array_slice($grammarConceptsUsed, 0, 5)));

        return "Analyze this complete paragraph and add fill-in-the-blank exercises to it.

COMPLETE PARAGRAPH (no blanks yet):
{$completeParagraph}

VOCABULARY WORDS USED IN PARAGRAPH:
{$vocabUsedText}

GRAMMAR CONCEPTS DEMONSTRATED IN PARAGRAPH:
{$grammarUsedText}

ALL AVAILABLE VOCABULARY WORDS:
{$vocabList}

ALL AVAILABLE GRAMMAR CONCEPTS:
{$grammarConceptsText}

CRITICAL REQUIREMENTS (MANDATORY - DO NOT DEVIATE):
1. ⚠️ YOU MUST ADD EXACTLY {$blankCount} BLANKS - NO MORE, NO LESS. If you add {$blankCount} blanks, the paragraph MUST contain exactly {$blankCount} instances of {{blank_X}} tokens.
2. Use EXACTLY these token formats in sequence: {{blank_1}}, {{blank_2}}, {{blank_3}}, ... {{blank_{$blankCount}}}
3. Extract the ACTUAL word/phrase from the paragraph as the correct answer for each blank
4. Include at least 1 vocabulary blank (replace a vocabulary word with {{blank_X}})
5. Include at least 1 grammar blank (replace a word/phrase that demonstrates a grammar concept with {{blank_X}})
6. Choose strategic locations for blanks - words that are important for understanding
7. For vocabulary blanks: The correct answer must be a word from the vocabulary_used list
8. For grammar blanks: The correct answer must demonstrate one of the grammar_concepts_used
9. Maintain the paragraph's grammatical correctness and flow
10. ⚠️ VALIDATION: Count your tokens before returning. The paragraph MUST contain exactly {$blankCount} tokens. If you have {$blankCount} blanks, you MUST have {$blankCount} tokens.
11. Each blank_id (blank_1, blank_2, etc.) must appear exactly once in the paragraph
12. ⚠️ YOUR RESPONSE WILL BE REJECTED IF THE TOKEN COUNT DOES NOT MATCH {$blankCount}

Return JSON with:
- paragraph: The paragraph with {{blank_1}}, {{blank_2}}, etc. tokens added (exactly {$blankCount} tokens)
- blanks: Array of exactly {$blankCount} objects, each with:
  - blank_id: \"blank_1\", \"blank_2\", etc. (MUST match the tokens in paragraph)
  - type: Either \"vocab\" or \"grammar\"
  - correct_answer: The ACTUAL word/phrase from the original paragraph that you replaced with {{blank_X}}
  - correct_vocab_text: For vocab blanks, this MUST equal correct_answer (the word string)
  - correct_vocab_id: For vocab blanks, optional vocabulary ID if you can identify it from the vocabulary_used list
  - sentence_context: The full sentence containing this blank, with {{blank_X}} showing where the blank is
  - grammar_concept_id: Required for grammar blanks - The ID of the grammar concept being tested
  - grammar_concept: Required for grammar blanks - The display name of the grammar concept";
    }

    /**
     * Build the prompt for generating distractors for a specific blank.
     */
    protected function buildDistractorsPrompt(string $blankId, string $blankType, string $sentenceContext, string $correctAnswer, array $vocabulary, array $grammarConcepts, ?int $grammarConceptId, ?string $grammarConcept): string
    {
        $vocabList = implode(', ', array_slice($vocabulary, 0, 20));
        
        if ($blankType === 'vocab') {
            return "Generate distractors for a vocabulary fill-in-the-blank exercise.

SENTENCE WITH BLANK:
{$sentenceContext}

CORRECT ANSWER (extracted from the original paragraph):
{$correctAnswer}

AVAILABLE VOCABULARY WORDS:
{$vocabList}

REQUIREMENTS:
1. The correct answer is already known: \"{$correctAnswer}\"
2. Generate exactly 3 distractors from the vocabulary list (exclude the correct answer)
3. All distractors MUST be grammatically incorrect or contextually wrong when placed in the sentence
4. Test each distractor: Read the sentence with the distractor inserted. Does it make sense? If yes, it's not a good distractor
5. Choose distractors that are plausible but clearly wrong in this context
6. No distractor should duplicate the correct answer

Return JSON with:
- distractors: Array of exactly 3 vocabulary words (strings) that don't fit in the sentence";
        } else {
            $conceptInfo = $grammarConceptId ? "Grammar Concept ID {$grammarConceptId}: {$grammarConcept}" : "Grammar concept from the available list";
            
            return "Generate distractors for a grammar fill-in-the-blank exercise.

SENTENCE WITH BLANK:
{$sentenceContext}

CORRECT ANSWER (extracted from the original paragraph):
{$correctAnswer}

GRAMMAR CONCEPT BEING TESTED:
{$conceptInfo}

REQUIREMENTS:
1. The correct answer is already known: \"{$correctAnswer}\"
2. Generate exactly 3 distractors that are grammatically incorrect when placed in this sentence
3. Test each distractor: Read the sentence with the distractor inserted. Is it grammatically wrong? If it could be correct, replace it
4. Distractors should be wrong forms:
   - Wrong tense (e.g., if correct is past tense, use present or future)
   - Wrong verb form (e.g., if correct is \"ask\", use \"asking\" or \"asked\" if wrong)
   - Wrong modal (e.g., if correct is \"should\", use \"must\", \"will\", \"can\" if wrong)
5. The correct answer must be the ONLY grammatically correct option for this blank
6. Each distractor must be clearly grammatically incorrect when placed in the sentence

Return JSON with:
- distractors: Array of exactly 3 grammatically incorrect options (strings)";
        }
    }

    /**
     * Build the prompt for OpenAI (old method - kept for backward compatibility but not used in new flow).
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

    /**
     * Fill in all blanks with correct answers to create completed paragraph
     */
    protected function fillBlanksWithCorrectAnswers(string $paragraph, array $blanks): string
    {
        $completed = $paragraph;
        $filledCount = 0;
        
        foreach ($blanks as $blankId => $blank) {
            $correctText = trim($blank['correct']['text'] ?? '');
            if (empty($correctText)) {
                Log::error('Cannot fill blank - missing correct answer', [
                    'blank_id' => $blankId,
                    'blank' => $blank
                ]);
                throw new \Exception("Cannot create completed paragraph: Blank {$blankId} has no correct answer.");
            }
            
            // Replace {{blank_id}} token with correct answer
            $token = "{{{$blankId}}}";
            $beforeReplace = $completed;
            $completed = str_replace($token, $correctText, $completed);
            
            // Verify replacement happened
            if ($completed === $beforeReplace) {
                Log::warning('Token not found in paragraph', [
                    'blank_id' => $blankId,
                    'token' => $token,
                    'paragraph_preview' => substr($paragraph, 0, 200)
                ]);
            } else {
                $filledCount++;
            }
        }
        
        // Verify all tokens were replaced
        $remainingTokens = $this->extractBlankTokens($completed);
        if (!empty($remainingTokens)) {
            Log::warning('Some tokens remain after filling blanks', [
                'remaining_tokens' => $remainingTokens,
                'filled_count' => $filledCount,
                'total_blanks' => count($blanks)
            ]);
        }
        
        return $completed;
    }
    
    /**
     * Validate that the completed paragraph makes grammatical and content sense
     */
    protected function validateCompletedParagraph(string $completedParagraph, string $lessonTitle, ?string $model = null): array
    {
        $systemMessage = 'You are an English language expert. Analyze a completed paragraph and determine if it makes grammatical and content sense. Check for: grammatical errors, logical flow, coherence, and whether the sentences make sense together.';
        
            $userMessage = "Analyze this completed paragraph from a fill-in-the-blank exercise. The blanks have been filled with the correct answers.\n\n" .
            "LESSON TITLE: {$lessonTitle}\n\n" .
            "COMPLETED PARAGRAPH:\n{$completedParagraph}\n\n" .
            "VALIDATION REQUIREMENTS:\n" .
            "1. Check if the paragraph is grammatically correct (no grammar errors, proper sentence structure)\n" .
            "2. Check if the sentences flow logically (smooth transitions, logical sequence)\n" .
            "3. Check if the content makes sense (no contradictions, logical connections between ideas)\n" .
            "4. Check if the paragraph is coherent and well-structured (clear topic, unified theme)\n" .
            "5. Check tense consistency (all verbs use appropriate and consistent tenses)\n" .
            "6. Check word choice (words fit the context and meaning)\n" .
            "7. Check if the paragraph reads naturally (not awkward or forced)\n\n" .
            "Be strict but fair. Only mark as invalid if there are clear grammatical errors, logical contradictions, or the paragraph doesn't make sense.\n\n" .
            "Return JSON with:\n" .
            "- valid: true if paragraph is grammatically correct and makes content sense, false otherwise\n" .
            "- errors: array of specific issues found (empty if valid is true). Be specific: e.g., 'Tense inconsistency: past tense mixed with present', 'Logical contradiction: says X but then contradicts it', 'Grammar error: subject-verb disagreement'\n" .
            "- explanation: brief explanation of why it's valid or what's wrong";
        
        $messages = [
            ['role' => 'system', 'content' => $systemMessage],
            ['role' => 'user', 'content' => $userMessage],
        ];
        
        $options = [
            'model' => $model ?? config('services.openai.translation_model', 'gpt-4o-mini'),
            'temperature' => 0.3, // Lower temperature for more consistent validation
            'response_format' => [
                'type' => 'json_schema',
                'json_schema' => [
                    'name' => 'paragraph_validation',
                    'schema' => [
                        'type' => 'object',
                        'required' => ['valid', 'errors'],
                        'properties' => [
                            'valid' => [
                                'type' => 'boolean',
                                'description' => 'True if paragraph is grammatically correct and makes content sense',
                            ],
                            'errors' => [
                                'type' => 'array',
                                'items' => ['type' => 'string'],
                                'description' => 'Array of specific issues found (empty if valid)',
                            ],
                            'explanation' => [
                                'type' => 'string',
                                'description' => 'Brief explanation of validation result',
                            ],
                        ],
                    ],
                ],
            ],
        ];
        
        try {
            $response = $this->openAiService->chatCompletion($messages, $options);
            $content = $this->openAiService->extractContent($response);
            
            if (!$content) {
                Log::warning('Self-check validation failed to get response');
                // If validation fails, assume valid (don't block on validation service failure)
                return ['valid' => true, 'errors' => [], 'explanation' => 'Validation service unavailable'];
            }
            
            $result = json_decode($content, true);
            
            if (!isset($result['valid'])) {
                Log::warning('Self-check validation returned invalid format', ['content' => $content]);
                return ['valid' => true, 'errors' => [], 'explanation' => 'Validation format error'];
            }
            
            return [
                'valid' => (bool)$result['valid'],
                'errors' => $result['errors'] ?? [],
                'explanation' => $result['explanation'] ?? '',
            ];
        } catch (\Exception $e) {
            Log::error('Self-check validation exception', [
                'error' => $e->getMessage(),
                'paragraph' => substr($completedParagraph, 0, 200),
            ]);
            // If validation fails, assume valid (don't block on validation service failure)
            return ['valid' => true, 'errors' => [], 'explanation' => 'Validation service error'];
        }
    }

⚠️ FINAL VERIFICATION - TEST EACH GRAMMAR BLANK:
1. Count how many blanks have type=\"vocab\" and how many have type=\"grammar\". You MUST have at least 1 of each.
2. For EACH grammar blank:
   a. Read the full sentence with the correct answer inserted. Is it grammatically correct? Does it match the grammar concept? If NO, fix it.
   b. Read the full sentence with EACH distractor inserted one at a time. Is it grammatically incorrect? If ANY distractor could be correct, replace it with a clearly wrong option.
   c. Verify the correct answer is the ONLY grammatically correct option for this blank in this sentence.
3. If any grammar blank fails these tests, regenerate that blank with better options.";
    }
}
