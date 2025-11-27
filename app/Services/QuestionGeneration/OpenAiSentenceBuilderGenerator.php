<?php

namespace App\Services\QuestionGeneration;

use App\Services\OpenAi\OpenAiService;
use Illuminate\Support\Facades\Log;

class OpenAiSentenceBuilderGenerator
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
     * Generate Sentence Builder questions from lesson content
     * 
     * @param array $lessonData Contains vocabulary, prompts, and lesson info
     * @param int $count Number of questions to generate (5-8)
     * @return array Array of question objects with correct_sentence, word_options
     */
    public function generateQuestions(array $lessonData, int $count = 6): array
    {
        if (!$this->enabled()) {
            throw new \Exception('OpenAI API key not configured');
        }

        $vocabulary = $lessonData['vocabulary'] ?? [];
        $prompts = $lessonData['prompts'] ?? [];
        $lessonTitle = $lessonData['title'] ?? 'Science Lesson';

        // Build context for AI
        $vocabList = array_map(fn($v) => $v['english_word'], $vocabulary);
        $promptTemplates = array_map(fn($p) => $p['template'], $prompts);
        
        $context = $this->buildContext($lessonTitle, $vocabList, $promptTemplates);

        try {
            $messages = [
                [
                    'role' => 'system',
                    'content' => 'You are an educational content creator for English language learners at CEFR A1 level (beginner). Generate sentence builder questions using VERY SIMPLE English. Use only basic vocabulary, simple present tense, and short sentences (3-5 words). No complex grammar, idioms, or difficult words.',
                ],
                [
                    'role' => 'user',
                    'content' => $context . "\n\nGenerate exactly {$count} sentence builder questions using CEFR A1 level English (beginner level).\n\nCRITICAL REQUIREMENTS:\n- Each question must have ONLY ONE valid sentence combination\n- Correct sentences should be 3-5 words\n- Use the lesson's vocabulary words and follow the style of the example sentences above\n- Include 2-4 distractor words that create invalid combinations\n- Distractors should be plausible but create grammar errors or nonsense\n- Use simple present tense: is, are, do, have, like, use\n- Use basic sentence structure: Subject + Verb + Object\n\nEXPLANATION REQUIREMENTS:\n- Keep explanations VERY SIMPLE (3-6 words)\n- Use simple present tense\n- Examples: 'We need water.', 'Fish live in water.', 'Plants grow well.'\n- DO NOT use: 'means', 'because', complex grammar, or long sentences\n- Just state a simple fact about the sentence\n\nFor each question, provide:\n- correct_sentence: array of words in correct order (3-5 words)\n- word_options: array of ALL words (correct + distractors, 6-10 words total)\n- explanation: VERY SIMPLE explanation (3-6 words, no 'means')\n- difficulty: 'easy' (3 words), 'medium' (4 words), or 'hard' (5 words)",
                ],
            ];

            $options = [
                'model' => config('services.openai.translation_model', 'gpt-4o-mini'),
                'temperature' => 0.7,
                'response_format' => [
                    'type' => 'json_schema',
                    'json_schema' => [
                        'name' => 'sentence_builder_questions',
                        'schema' => [
                            'type' => 'object',
                            'required' => ['questions'],
                            'properties' => [
                                'questions' => [
                                    'type' => 'array',
                                    'items' => [
                                        'type' => 'object',
                                        'required' => ['correct_sentence', 'word_options', 'explanation', 'difficulty'],
                                        'properties' => [
                                            'correct_sentence' => [
                                                'type' => 'array',
                                                'items' => ['type' => 'string'],
                                                'description' => 'Array of words in the correct sentence order (3-5 words). Example: ["I", "like", "fish"]',
                                            ],
                                            'word_options' => [
                                                'type' => 'array',
                                                'items' => ['type' => 'string'],
                                                'description' => 'Array of ALL words including correct words and distractors (6-10 words total). Must include all words from correct_sentence plus 2-4 distractors.',
                                            ],
                                            'explanation' => [
                                                'type' => 'string',
                                                'description' => 'Simple explanation using CEFR A1 level English. Keep it short and clear. Examples: "We need water.", "Fish live in water.", "Plants grow with water." Do NOT use "means" or complex grammar.',
                                            ],
                                            'difficulty' => [
                                                'type' => 'string',
                                                'enum' => ['easy', 'medium', 'hard'],
                                                'description' => 'easy = 3 words, medium = 4 words, hard = 5 words',
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

            // Validate each question has only one valid combination
            $validatedQuestions = [];
            foreach ($data['questions'] as $question) {
                if ($this->validateSingleCombination($question)) {
                    $validatedQuestions[] = $question;
                } else {
                    Log::warning('Skipping question with multiple valid combinations', [
                        'question' => $question,
                    ]);
                }
            }

            return $validatedQuestions;

        } catch (\Throwable $e) {
            Log::error('OpenAI sentence builder generation exception', [
                'message' => $e->getMessage(),
                'lesson' => $lessonTitle,
            ]);
            throw $e;
        }
    }

    /**
     * Validate that a question has only one valid sentence combination
     * This is a simple check - AI should handle the main validation
     */
    protected function validateSingleCombination(array $question): bool
    {
        $correctSentence = $question['correct_sentence'] ?? [];
        $wordOptions = $question['word_options'] ?? [];

        // Basic validation
        if (empty($correctSentence) || empty($wordOptions)) {
            return false;
        }

        // Check that all correct words are in options
        foreach ($correctSentence as $word) {
            if (!in_array($word, $wordOptions)) {
                return false;
            }
        }

        // Check that there are distractors (more options than correct words)
        if (count($wordOptions) <= count($correctSentence)) {
            return false;
        }

        return true;
    }

    /**
     * Build context string for AI prompt
     */
    protected function buildContext(string $lessonTitle, array $vocabulary, array $promptTemplates): string
    {
        $vocabText = !empty($vocabulary) 
            ? "Vocabulary words: " . implode(', ', array_slice($vocabulary, 0, 20))
            : "No vocabulary words available.";
        
        // Build example sentences from prompt templates
        $exampleSentences = [];
        if (!empty($promptTemplates) && !empty($vocabulary)) {
            foreach (array_slice($promptTemplates, 0, 5) as $template) {
                // Try to fill template with vocabulary words
                foreach (array_slice($vocabulary, 0, 5) as $word) {
                    $filledSentence = str_replace('{}', $word, $template);
                    // Split into words for sentence builder format
                    $words = preg_split('/\s+/', trim($filledSentence));
                    // Only include if 3-5 words
                    if (count($words) >= 3 && count($words) <= 5) {
                        $exampleSentences[] = [
                            'template' => $template,
                            'filled' => $filledSentence,
                            'words' => $words,
                        ];
                        break; // One example per template
                    }
                }
            }
        }
        
        $promptsText = !empty($promptTemplates)
            ? "Sentence templates from this lesson:\n" . implode("\n", array_slice($promptTemplates, 0, 10))
            : "No prompts available.";
        
        $examplesText = '';
        if (!empty($exampleSentences)) {
            $examplesText = "\n\nEXAMPLE SENTENCES (based on templates + vocabulary):\n";
            foreach ($exampleSentences as $ex) {
                $examplesText .= "- Template: \"{$ex['template']}\"\n";
                $examplesText .= "  Filled: \"{$ex['filled']}\"\n";
                $examplesText .= "  Words: [" . implode(', ', array_map(fn($w) => '"' . $w . '"', $ex['words'])) . "]\n\n";
            }
        }

        return <<<CONTEXT
Lesson Title: {$lessonTitle}

{$vocabText}

{$promptsText}{$examplesText}

Generate sentence builder questions based on this lesson's templates and vocabulary using CEFR A1 level English (beginner level).

CRITICAL LANGUAGE REQUIREMENTS:
- Use ONLY simple, common words (A1 vocabulary)
- Use simple present tense: is, are, do, have, like, use, make
- Keep sentences SHORT (3-5 words maximum)
- Simple sentence structure: Subject + Verb + Object
- NO complex grammar, idioms, or difficult words
- Follow the style and structure of the example sentences above

DISTRACTOR REQUIREMENTS:
- Include words that create grammar errors (e.g., "likes" when subject is "I")
- Include words that create nonsense (e.g., "cat" when talking about water)
- Include words that are wrong form (e.g., "are" when subject is singular)
- Ensure distractors are plausible but create invalid combinations

VALIDATION:
- Each question must have EXACTLY ONE valid sentence combination
- All other combinations must be grammatically incorrect or nonsensical
CONTEXT;
    }
}

