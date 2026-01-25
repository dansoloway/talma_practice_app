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
     * Generate True/False questions from lesson content
     * 
     * @param array $lessonData Contains vocabulary, prompts, lesson info, and optional grammar_set
     * @param int $count Number of questions to generate (5-8)
     * @return array Array of question objects with statement, is_true, explanation
     */
    public function generateQuestions(array $lessonData, int $count = 6): array
    {
        if (!$this->enabled()) {
            throw new \Exception('OpenAI API key not configured');
        }

        $vocabulary = $lessonData['vocabulary'] ?? [];
        $prompts = $lessonData['prompts'] ?? [];
        $lessonTitle = $lessonData['title'] ?? 'Science Lesson';
        $grammarSet = $lessonData['grammar_set'] ?? null;

        // Build context for AI
        $vocabList = array_map(fn($v) => $v['english_word'], $vocabulary);
        $promptTemplates = array_map(fn($p) => $p['template'], $prompts);
        
        $context = $this->buildContext($lessonTitle, $vocabList, $promptTemplates, $grammarSet);

        try {
            // Build grammar-focused instructions if grammar set is provided
            $grammarInstructions = '';
            if ($grammarSet && !empty($grammarSet['concepts'])) {
                $conceptNames = array_map(fn($c) => $c['display_name'], $grammarSet['concepts']);
                $grammarInstructions = "\n\nGRAMMAR FOCUS:\nThe questions should test understanding of these grammar concepts:\n" . implode("\n", array_slice($conceptNames, 0, 10));
                if (count($conceptNames) > 10) {
                    $grammarInstructions .= "\n(and " . (count($conceptNames) - 10) . " more concepts)";
                }
                $grammarInstructions .= "\n\nCreate questions that specifically test these grammar rules. For example:\n";
                $grammarInstructions .= "- If testing 'Modals - can', create statements like 'We can swim' or 'Birds can fly'\n";
                $grammarInstructions .= "- If testing 'Present Simple', create statements using present simple tense\n";
                $grammarInstructions .= "- Make sure the grammar in each statement demonstrates or tests the selected concepts\n";
            }

            $messages = [
                [
                    'role' => 'system',
                    'content' => 'You are an educational content creator for English language learners at CEFR A1 level (beginner). Generate True/False questions using VERY SIMPLE English. Use only basic vocabulary, simple present tense, and short sentences (5-10 words). No complex grammar, idioms, or difficult words. Questions should be clear, simple, and easy to understand for beginners learning English.' . ($grammarSet ? ' When a grammar set is provided, focus questions on testing those specific grammar concepts while maintaining A1 level simplicity.' : ''),
                ],
                [
                    'role' => 'user',
                    'content' => $context . "\n\nGenerate exactly {$count} True/False questions using CEFR A1 level English (beginner level).\n\nIMPORTANT LANGUAGE REQUIREMENTS:\n- Use ONLY simple, common words\n- Use simple present tense (is, are, do, have)\n- Keep sentences SHORT (5-10 words maximum)\n- Use basic sentence structure: Subject + Verb + Object\n- NO complex grammar, idioms, or difficult vocabulary\n- Examples of A1 level: 'Ice is cold', 'Water is wet', 'We use paper'\n- Examples to AVOID: 'Ice undergoes a phase transition', 'Water exhibits liquid properties'\n\nMix true and false statements. Include questions about vocabulary definitions, procedural steps, science facts, and common misconceptions." . $grammarInstructions,
                ],
            ];

            $options = [
                'model' => config('services.openai.translation_model', 'gpt-4o-mini'),
                'temperature' => 0.7, // Slightly creative for variety
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
                                        'required' => ['statement', 'is_true', 'explanation'],
                                        'properties' => [
                                            'statement' => [
                                                'type' => 'string',
                                                'description' => 'The statement for the True/False question. MUST use CEFR A1 level English: simple words, short sentences (5-10 words), simple present tense. Examples: "Ice is cold", "We use paper", "Water is wet".',
                                            ],
                                            'is_true' => [
                                                'type' => 'boolean',
                                                'description' => 'Whether the statement is true or false',
                                            ],
                                            'explanation' => [
                                                'type' => 'string',
                                                'description' => 'Brief explanation using CEFR A1 level English (simple words, short sentences). Examples: "Yes! Ice is cold.", "No. Water is wet."',
                                            ],
                                            'category' => [
                                                'type' => 'string',
                                                'enum' => ['science_facts', 'procedures', 'vocabulary', 'process', 'misconception'],
                                                'description' => 'Category of the question',
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

            return $data['questions'];

        } catch (\Throwable $e) {
            Log::error('OpenAI question generation exception', [
                'message' => $e->getMessage(),
                'lesson' => $lessonTitle,
            ]);
            throw $e;
        }
    }

    /**
     * Build context string for AI prompt
     */
    protected function buildContext(string $lessonTitle, array $vocabulary, array $promptTemplates, ?array $grammarSet = null): string
    {
        $vocabText = !empty($vocabulary) 
            ? "Vocabulary words: " . implode(', ', array_slice($vocabulary, 0, 20))
            : "No vocabulary words available.";
        
        $promptsText = !empty($promptTemplates)
            ? "Sample sentence templates:\n" . implode("\n", array_slice($promptTemplates, 0, 10))
            : "No prompts available.";

        $grammarText = '';
        if ($grammarSet && !empty($grammarSet['concepts'])) {
            $conceptList = array_map(function($c) {
                return "- " . $c['display_name'];
            }, array_slice($grammarSet['concepts'], 0, 15));
            
            $grammarText = "\n\nGrammar Set: {$grammarSet['title']}\n";
            $grammarText .= "Grammar Concepts to focus on:\n" . implode("\n", $conceptList);
            if (count($grammarSet['concepts']) > 15) {
                $grammarText .= "\n(and " . (count($grammarSet['concepts']) - 15) . " more concepts)";
            }
        }

        return <<<CONTEXT
Lesson Title: {$lessonTitle}

{$vocabText}

{$promptsText}
{$grammarText}

Generate True/False questions based on this content using CEFR A1 level English (beginner level).

CRITICAL LANGUAGE REQUIREMENTS:
- Use ONLY simple, common words (A1 vocabulary)
- Use simple present tense: is, are, do, have, make, use
- Keep sentences SHORT (5-10 words maximum)
- Simple sentence structure: Subject + Verb + Object
- NO complex grammar, idioms, or difficult words
- NO passive voice, conditionals, or complex tenses
- Use basic vocabulary from the lesson

Examples of GOOD A1 level questions:
- "Ice is cold" (TRUE)
- "Water is hot" (FALSE)
- "We use paper" (TRUE)
- "Ice melts in sun" (TRUE)
- "Paper is metal" (FALSE)

Examples to AVOID (too complex):
- "Ice undergoes a phase transition" ❌
- "Water exhibits liquid properties" ❌
- "The process involves multiple steps" ❌

Questions should:
- Test understanding of vocabulary words (simple definitions)
- Check comprehension of procedural steps (simple actions)
- Reinforce science facts (simple statements)
- Address common misconceptions (simple corrections)
CONTEXT;
    }
}

