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
            // Determine if we should use grammar-focused generation
            $hasGrammarSet = $grammarSet && !empty($grammarSet['concepts']);
            
            // Build grammar-focused instructions if grammar set is provided
            $grammarInstructions = '';
            $systemPrompt = 'You are an educational content creator for English language learners. Generate True/False questions that are clear, grammatically correct, and factually accurate.';
            
            if ($hasGrammarSet) {
                $concepts = $grammarSet['concepts'];
                $conceptList = array_map(function($c) {
                    return "- " . $c['display_name'] . " (" . ($c['grammar_topic'] ?? '') . ($c['grammar_sub_topic'] ? ' - ' . $c['grammar_sub_topic'] : '') . ")";
                }, array_slice($concepts, 0, 15));
                
                $grammarInstructions = "\n\nCRITICAL: You MUST create questions that TEST these specific grammar concepts:\n" . implode("\n", $conceptList);
                if (count($concepts) > 15) {
                    $grammarInstructions .= "\n(and " . (count($concepts) - 15) . " more concepts)";
                }
                
                $grammarInstructions .= "\n\nHOW TO CREATE GRAMMAR-FOCUSED QUESTIONS:\n";
                $grammarInstructions .= "1. Each statement MUST USE the grammar form being tested\n";
                $grammarInstructions .= "2. The statement should be grammatically correct if TRUE, or grammatically incorrect if FALSE\n";
                $grammarInstructions .= "3. Use vocabulary from the lesson when possible\n";
                $grammarInstructions .= "4. Make statements FACTUALLY ACCURATE and LOGICAL\n";
                $grammarInstructions .= "5. Set the 'category' field to match the grammar concept being tested\n\n";
                
                $grammarInstructions .= "EXAMPLES:\n";
                $grammarInstructions .= "- Testing 'Present progressive - positive': 'The wind is blowing now.' (TRUE) - Uses 'is + verb-ing'\n";
                $grammarInstructions .= "- Testing 'Present simple - negative': 'Sand is not wet.' (FALSE - sand CAN be wet) - Uses 'is not'\n";
                $grammarInstructions .= "- Testing 'Present simple - questions': 'Is an oasis dry?' (FALSE - oases have water) - Uses question form\n";
                $grammarInstructions .= "- Testing 'Present progressive - negative': 'I am not thirsty now.' (TRUE) - Uses 'am not + verb-ing'\n\n";
                
                $grammarInstructions .= "IMPORTANT:\n";
                $grammarInstructions .= "- Each question should test ONE specific grammar concept\n";
                $grammarInstructions .= "- Statements must be FACTUALLY CORRECT (if TRUE) or FACTUALLY INCORRECT (if FALSE)\n";
                $grammarInstructions .= "- Avoid ambiguous statements like 'Sand is not wet' - be specific and accurate\n";
                $grammarInstructions .= "- Use appropriate vocabulary for the grade level\n";
                
                // Adjust system prompt for grammar-focused generation
                $systemPrompt = 'You are an educational content creator for English language learners. Generate True/False questions that test specific grammar concepts. Questions must be grammatically correct, factually accurate, and logically sound.';
            } else {
                // Default A1 level for general questions
                $systemPrompt = 'You are an educational content creator for English language learners at CEFR A1 level (beginner). Generate True/False questions using VERY SIMPLE English. Use only basic vocabulary, simple present tense, and short sentences (5-10 words). No complex grammar, idioms, or difficult words.';
            }

            $userPrompt = $context . "\n\nGenerate exactly {$count} True/False questions.";
            
            if ($hasGrammarSet) {
                $userPrompt .= "\n\nGRAMMAR REQUIREMENTS:\n";
                $userPrompt .= "- Each question MUST test one of the grammar concepts listed above\n";
                $userPrompt .= "- Use the EXACT grammar form being tested in each statement\n";
                $userPrompt .= "- Make statements factually accurate and logically sound\n";
                $userPrompt .= "- Set 'category' to match the grammar concept (e.g., 'Present progressive - positive')\n";
            } else {
                $userPrompt .= "\n\nLANGUAGE REQUIREMENTS:\n";
                $userPrompt .= "- Use ONLY simple, common words (A1 vocabulary)\n";
                $userPrompt .= "- Use simple present tense (is, are, do, have)\n";
                $userPrompt .= "- Keep sentences SHORT (5-10 words maximum)\n";
                $userPrompt .= "- Simple sentence structure: Subject + Verb + Object\n";
                $userPrompt .= "- NO complex grammar, idioms, or difficult vocabulary\n";
            }
            
            $userPrompt .= "\n\nMix true and false statements. Ensure all statements are factually accurate and make logical sense.";

            $messages = [
                [
                    'role' => 'system',
                    'content' => $systemPrompt,
                ],
                [
                    'role' => 'user',
                    'content' => $userPrompt . $grammarInstructions,
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
                                                'description' => 'Category of the question. If testing a grammar concept, use the grammar concept name (e.g., "Present progressive - positive", "Present simple - negative"). Otherwise use: science_facts, procedures, vocabulary, process, or misconception.',
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

        $baseContext = <<<CONTEXT
Lesson Title: {$lessonTitle}

{$vocabText}

{$promptsText}
{$grammarText}
CONTEXT;

        // Only add A1 level instructions if no grammar set is provided
        if (!$grammarSet || empty($grammarSet['concepts'])) {
            $baseContext .= <<<CONTEXT


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

        return $baseContext;
    }
}

