<?php

namespace App\Services\QuestionGeneration;

use App\Services\OpenAi\OpenAiService;
use Illuminate\Support\Facades\Log;

class OpenAiQuestionGenerator
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
     * Generate vocabulary-focused True/False questions
     * 
     * @param array $lessonData Contains vocabulary, lesson info, game_version
     * @param int $count Number of questions to generate
     * @return array Array of question objects with statement, is_true, explanation, category, vocab_words
     */
    public function generateQuestions(array $lessonData, int $count = 6): array
    {
        if (!$this->enabled()) {
            throw new \Exception('OpenAI API key not configured');
        }

        $vocabulary = $lessonData['vocabulary'] ?? [];
        $lessonTitle = $lessonData['title'] ?? 'Lesson';
        $gameVersion = $lessonData['game_version'] ?? 'easy';

        if (empty($vocabulary)) {
            throw new \Exception('No vocabulary provided for question generation');
        }

        // Build vocabulary list with words
        $vocabList = array_map(fn($v) => $v['english_word'], $vocabulary);
        $vocabWords = implode(', ', array_slice($vocabList, 0, 30));
        
        $context = $this->buildContext($lessonTitle, $vocabWords, $gameVersion);
        $systemPrompt = $this->buildSystemPrompt($gameVersion);
        $userPrompt = $this->buildUserPrompt($context, $gameVersion, $count, $vocabList);

        try {
            $messages = [
                [
                    'role' => 'system',
                    'content' => $systemPrompt,
                ],
                [
                    'role' => 'user',
                    'content' => $userPrompt,
                ],
            ];

            $options = [
                'model' => config('services.openai.translation_model', 'gpt-4o-mini'),
                'temperature' => 0.7,
                'response_format' => [
                    'type' => 'json_schema',
                    'json_schema' => [
                        'name' => 'true_false_questions',
                        'schema' => [
                            'type' => 'object',
                            'required' => ['questions'],
                            'properties' => [
                                'questions' => [
                                    'type' => 'array',
                                    'items' => [
                                        'type' => 'object',
                                        'required' => ['statement', 'is_true', 'explanation', 'vocab_words'],
                                        'properties' => [
                                            'statement' => [
                                                'type' => 'string',
                                                'description' => 'The True/False statement. Must be at least 6 words. Must NOT contain "?", "means", "a kind of", "a type of".',
                                            ],
                                            'is_true' => [
                                                'type' => 'boolean',
                                                'description' => 'Whether the statement correctly reflects the vocabulary meaning/usage',
                                            ],
                                            'explanation' => [
                                                'type' => 'string',
                                                'description' => 'Brief, learner-friendly explanation (1-2 sentences)',
                                            ],
                                            'category' => [
                                                'type' => 'string',
                                                'description' => 'Use the vocabulary word being tested, or a simple reasoning label',
                                            ],
                                            'vocab_words' => [
                                                'type' => 'array',
                                                'items' => ['type' => 'string'],
                                                'description' => 'Array of vocabulary words from the provided list that this question tests (at least 1 required)',
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'timeout' => 60,
            ];

            $response = $this->openAiService->chatCompletion($messages, $options);

            if (!$response) {
                throw new \Exception('Failed to generate questions: OpenAI service returned null');
            }

            $content = $this->openAiService->extractContent($response);
            if (!$content) {
                throw new \Exception('No content in OpenAI response');
            }

            $data = json_decode($content, true);
            if (!is_array($data) || !isset($data['questions'])) {
                throw new \Exception('Invalid response format from OpenAI');
            }

            // Validate and filter questions
            $validatedQuestions = $this->validateQuestions($data['questions'], $vocabList, $gameVersion, $count);

            return $validatedQuestions;

        } catch (\Throwable $e) {
            Log::error('OpenAI question generation exception', [
                'message' => $e->getMessage(),
                'lesson' => $lessonTitle,
                'game_version' => $gameVersion,
            ]);
            throw $e;
        }
    }

    /**
     * Build system prompt based on game version
     */
    protected function buildSystemPrompt(string $gameVersion): string
    {
        $basePrompt = 'You are an educational content creator for English language learners. Generate True/False questions that test VOCABULARY understanding. TRUE or FALSE refers ONLY to whether the sentence correctly reflects the meaning or usage of the target vocabulary word(s).';

        $difficultyRules = match($gameVersion) {
            'easy' => "\n\nEASY LEVEL RULES:\n- Direct meaning or basic usage\n- Clear, concrete sentences\n- No negation\n- No tricks\n- No subtlety\n- DO NOT use definition phrasing like 'X means Y'\n\nExamples:\n- TRUE: 'An oasis is a place with water in a desert.'\n- FALSE: 'Dry ground has a lot of water.'",
            
            'medium' => "\n\nMEDIUM LEVEL RULES:\nGoal: normal classroom practice\n\nAllowed: EXACTLY ONE of the following per sentence:\n1) Everyday usage context (meaning shown through action)\n2) Simple contrast (clear opposite, not subtle)\n3) Single-step inference (meaning implied but obvious)\n\nRules:\n- ❌ NO double negatives\n- ❌ NO layered or tricky negation\n- ❌ NO near-miss traps\n- ❌ NO external knowledge\n- Sentence should be understandable in ONE read\n\nExamples:\n- TRUE: 'We rested at an oasis and drank water.'\n- FALSE: 'An oasis is a place with no water.'\n- TRUE: 'After hours in the desert, finding an oasis was a relief.'",
            
            'hard' => "\n\nHARD LEVEL RULES:\nGoal: vocabulary mastery and precision\n\nAllowed:\n- Near-miss meanings\n- Partial correctness (almost right, one detail wrong)\n- Subtle incorrect details\n- Careful negation (still avoid double negatives unless unavoidable)\n- Optional two-vocabulary interaction\n\nExamples:\n- FALSE: 'An oasis is a dry desert area where plants cannot grow.'\n- FALSE: 'Windy and stormy describe the same kind of weather.'",
            
            default => '',
        };

        return $basePrompt . $difficultyRules;
    }

    /**
     * Build user prompt
     */
    protected function buildUserPrompt(string $context, string $gameVersion, int $count, array $vocabList): string
    {
        $bannedPatterns = "\n\nBANNED PATTERNS (ALL LEVELS):\n- No questions (no '?')\n- No dictionary glosses: 'X means…'\n- No 'a kind of', 'a type of'\n- No vague science facts\n- No trivial statements ('X is big Y')\n- No grammar labels as categories\n- No duplicates (within batch or lesson + version)";

        return <<<PROMPT
{$context}

Generate exactly {$count} True/False questions for {$gameVersion} level.

CRITICAL REQUIREMENTS:
- Each question MUST target at least one vocabulary word from: {$this->formatVocabList($vocabList)}
- Statement must be at least 6 words
- Statement must NOT contain: "?", "means", "a kind of", "a type of"
- TRUE means the sentence correctly reflects vocabulary meaning/usage
- FALSE means the sentence incorrectly reflects vocabulary meaning/usage
- Include 'vocab_words' array listing which words are tested
- Set 'category' to the vocabulary word being tested (or simple reasoning label)
- Ensure all statements are factually accurate and logically sound
- Mix TRUE and FALSE statements
{$bannedPatterns}

Return as JSON array of questions.
PROMPT;
    }

    /**
     * Build context string
     */
    protected function buildContext(string $lessonTitle, string $vocabWords, string $gameVersion): string
    {
        return <<<CONTEXT
Lesson Title: {$lessonTitle}
Game Version: {$gameVersion}
Available Vocabulary: {$vocabWords}

Generate True/False questions that test understanding of these vocabulary words.
CONTEXT;
    }

    /**
     * Format vocabulary list for prompt
     */
    protected function formatVocabList(array $vocabList): string
    {
        return implode(', ', array_slice($vocabList, 0, 30)) . (count($vocabList) > 30 ? ' (and more)' : '');
    }

    /**
     * Validate generated questions
     */
    protected function validateQuestions(array $questions, array $allowedVocab, string $gameVersion, int $expectedCount): array
    {
        $validated = [];
        $seenStatements = [];
        $allowedVocabLower = array_map('strtolower', $allowedVocab);

        foreach ($questions as $question) {
            $errors = [];

            // Check required fields
            if (empty($question['statement'])) {
                $errors[] = 'Missing statement';
                continue;
            }

            if (!isset($question['is_true'])) {
                $errors[] = 'Missing is_true';
                continue;
            }

            if (empty($question['vocab_words']) || !is_array($question['vocab_words'])) {
                $errors[] = 'Missing or invalid vocab_words';
                continue;
            }

            $statement = trim($question['statement']);
            $statementLower = strtolower($statement);

            // Check banned patterns
            if (strpos($statement, '?') !== false) {
                $errors[] = 'Contains question mark';
            }

            $bannedPhrases = ['means', 'a kind of', 'a type of'];
            foreach ($bannedPhrases as $phrase) {
                if (stripos($statement, $phrase) !== false) {
                    $errors[] = "Contains banned phrase: {$phrase}";
                }
            }

            // Check minimum length
            $wordCount = str_word_count($statement);
            if ($wordCount < 6) {
                $errors[] = "Too short ({$wordCount} words, need 6+)";
            }

            // Check vocabulary usage
            $vocabWords = array_map('strtolower', $question['vocab_words']);
            $vocabFound = false;
            foreach ($vocabWords as $vocab) {
                if (in_array($vocab, $allowedVocabLower)) {
                    $vocabFound = true;
                    break;
                }
            }
            if (!$vocabFound) {
                $errors[] = 'No valid vocabulary words from allowed list';
            }

            // Check for duplicates (normalized)
            $normalized = $this->normalizeStatement($statement);
            if (isset($seenStatements[$normalized])) {
                $errors[] = 'Duplicate statement';
            }

            // Medium-specific validation
            if ($gameVersion === 'medium') {
                if ($this->hasDoubleNegative($statement)) {
                    $errors[] = 'Medium level: Contains double negative';
                }
                if ($this->hasMultipleReasoningLevers($statement)) {
                    $errors[] = 'Medium level: Has multiple reasoning levers';
                }
            }

            if (empty($errors)) {
                $validated[] = $question;
                $seenStatements[$normalized] = true;
            } else {
                Log::warning('Question validation failed', [
                    'statement' => $statement,
                    'errors' => $errors,
                ]);
            }
        }

        // If we don't have enough valid questions, log warning
        if (count($validated) < $expectedCount) {
            Log::warning('Insufficient valid questions generated', [
                'expected' => $expectedCount,
                'valid' => count($validated),
                'game_version' => $gameVersion,
            ]);
        }

        return $validated;
    }

    /**
     * Normalize statement for duplicate detection
     */
    protected function normalizeStatement(string $statement): string
    {
        // Lowercase, remove punctuation, normalize whitespace
        $normalized = strtolower($statement);
        $normalized = preg_replace('/[^\w\s]/', '', $normalized);
        $normalized = preg_replace('/\s+/', ' ', $normalized);
        return trim($normalized);
    }

    /**
     * Check for double negative
     */
    protected function hasDoubleNegative(string $statement): bool
    {
        $negatives = ['not', 'no', 'never', 'nothing', 'nobody', 'nowhere', 'none'];
        $lower = strtolower($statement);
        $count = 0;
        foreach ($negatives as $neg) {
            if (preg_match('/\b' . preg_quote($neg, '/') . '\b/', $lower)) {
                $count++;
            }
        }
        return $count > 1;
    }

    /**
     * Check if statement has multiple reasoning levers (for medium level)
     */
    protected function hasMultipleReasoningLevers(string $statement): bool
    {
        // Simple heuristic: check for multiple conjunctions or complex structure
        $conjunctions = ['and', 'but', 'or', 'because', 'although', 'while', 'when'];
        $lower = strtolower($statement);
        $count = 0;
        foreach ($conjunctions as $conj) {
            if (preg_match('/\b' . preg_quote($conj, '/') . '\b/', $lower)) {
                $count++;
            }
        }
        // More than one conjunction suggests multiple reasoning levers
        return $count > 1;
    }
}
