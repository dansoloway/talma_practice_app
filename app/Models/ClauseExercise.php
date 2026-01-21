<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ClauseExercise extends Model
{
    use HasFactory;

    protected $fillable = [
        'lesson_id',
        'grammar_set_id',
        'title',
        'topic',
        'paragraph_text',
        'correct_answers', // Kept for backward compatibility
        'blank_positions', // Kept for backward compatibility
        'blank_metadata', // Kept for backward compatibility
        'blanks', // New single source of truth
        'difficulty_level',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'correct_answers' => 'array',
        'blank_positions' => 'array',
        'blank_metadata' => 'array',
        'blanks' => 'array', // New format
        'is_active' => 'boolean',
    ];

    /**
     * The relationships that should be touched when this model is updated.
     */
    protected $touches = ['lesson'];

    /**
     * Get the lesson this exercise belongs to.
     */
    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    /**
     * Get the grammar set this exercise uses.
     */
    public function grammarSet(): BelongsTo
    {
        return $this->belongsTo(GrammarSet::class);
    }

    /**
     * Get the vocabulary items used in this exercise.
     */
    public function vocabulary(): BelongsToMany
    {
        // Support both old and new format
        $vocabularyIds = [];
        if ($this->blanks) {
            // New format: extract vocab IDs from blanks
            foreach ($this->blanks as $blank) {
                if (($blank['type'] ?? '') === 'vocab') {
                    if (isset($blank['correct']['vocab_id'])) {
                        $vocabularyIds[] = $blank['correct']['vocab_id'];
                    }
                    foreach ($blank['distractors'] ?? [] as $distractor) {
                        if (isset($distractor['vocab_id'])) {
                            $vocabularyIds[] = $distractor['vocab_id'];
                        }
                    }
                }
            }
        } else {
            // Old format: use correct_answers
            $vocabularyIds = collect($this->correct_answers ?? [])->values()->unique()->toArray();
        }
        return $this->lesson->vocabulary()->whereIn('id', $vocabularyIds);
    }

    /**
     * Get blanks in new format, with backward compatibility
     * Properly converts old format ({} placeholders + blank_metadata) to new format ({{blank_id}} tokens)
     */
    public function getBlanksAttribute($value)
    {
        $blanks = json_decode($value, true) ?? [];
        
        // If already new format (object keyed by blank_id with type field), return as-is
        if (!empty($blanks) && isset($blanks['blank_1']) && isset($blanks['blank_1']['type'])) {
            return $blanks;
        }
        
        // Old format: convert blank_metadata + correct_answers + paragraph_text to new format
        $oldMetadata = $this->blank_metadata ?? [];
        $correctAnswers = $this->correct_answers ?? [];
        $paragraphText = $this->paragraph_text ?? '';
        $converted = [];
        
        // Check if paragraph_text has old {} placeholders
        $hasOldPlaceholders = strpos($paragraphText, '{}') !== false;
        
        if ($hasOldPlaceholders && !empty($oldMetadata)) {
            // Old format: map {} placeholders by order to blank_metadata
            $blankIds = array_keys($oldMetadata);
            $placeholderCount = substr_count($paragraphText, '{}');
            
            // Match placeholders to blanks by order
            for ($i = 0; $i < min($placeholderCount, count($blankIds)); $i++) {
                $blankId = $blankIds[$i];
                $metadata = $oldMetadata[$blankId];
                
                // Get correct answer - for vocab it's an ID, for grammar it's text
                $correctAnswer = $correctAnswers[$blankId] ?? '';
                
                if (($metadata['type'] ?? '') === 'vocab') {
                    // Vocab: correct_answer is vocab ID, need to get text from vocabulary
                    $vocabId = is_numeric($correctAnswer) ? (int)$correctAnswer : null;
                    $vocabText = '';
                    if ($vocabId) {
                        $vocab = $this->lesson->vocabulary()->find($vocabId);
                        $vocabText = $vocab ? $vocab->english_word : '';
                    }
                    
                    $converted[$blankId] = [
                        'type' => 'vocab',
                        'correct' => [
                            'text' => $vocabText,
                            'vocab_id' => $vocabId,
                        ],
                        'distractors' => array_map(function($distId) {
                            $distVocab = $this->lesson->vocabulary()->find($distId);
                            return [
                                'text' => $distVocab ? $distVocab->english_word : '',
                                'vocab_id' => is_numeric($distId) ? (int)$distId : null,
                            ];
                        }, $metadata['distractors'] ?? []),
                    ];
                } else {
                    // Grammar: correct_answer is text string
                    $converted[$blankId] = [
                        'type' => 'grammar',
                        'correct' => [
                            'text' => is_string($correctAnswer) ? $correctAnswer : '',
                        ],
                        'distractors' => array_map(function($dist) {
                            return ['text' => is_string($dist) ? $dist : ''];
                        }, $metadata['distractors'] ?? []),
                        'grammar_concept_id' => $metadata['grammar_concept_id'] ?? null,
                        'grammar_concept' => $metadata['grammar_concept'] ?? '',
                    ];
                }
            }
        } elseif (!empty($oldMetadata)) {
            // Fallback: convert without paragraph_text mapping
            foreach ($oldMetadata as $blankId => $metadata) {
                $correctAnswer = $correctAnswers[$blankId] ?? '';
                
                if (($metadata['type'] ?? '') === 'vocab') {
                    $vocabId = is_numeric($correctAnswer) ? (int)$correctAnswer : null;
                    $vocabText = '';
                    if ($vocabId) {
                        $vocab = $this->lesson->vocabulary()->find($vocabId);
                        $vocabText = $vocab ? $vocab->english_word : '';
                    }
                    
                    $converted[$blankId] = [
                        'type' => 'vocab',
                        'correct' => [
                            'text' => $vocabText,
                            'vocab_id' => $vocabId,
                        ],
                        'distractors' => array_map(function($distId) {
                            $distVocab = $this->lesson->vocabulary()->find($distId);
                            return [
                                'text' => $distVocab ? $distVocab->english_word : '',
                                'vocab_id' => is_numeric($distId) ? (int)$distId : null,
                            ];
                        }, $metadata['distractors'] ?? []),
                    ];
                } else {
                    $converted[$blankId] = [
                        'type' => 'grammar',
                        'correct' => [
                            'text' => is_string($correctAnswer) ? $correctAnswer : '',
                        ],
                        'distractors' => array_map(function($dist) {
                            return ['text' => is_string($dist) ? $dist : ''];
                        }, $metadata['distractors'] ?? []),
                        'grammar_concept_id' => $metadata['grammar_concept_id'] ?? null,
                        'grammar_concept' => $metadata['grammar_concept'] ?? '',
                    ];
                }
            }
        }
        
        return $converted;
    }
}
