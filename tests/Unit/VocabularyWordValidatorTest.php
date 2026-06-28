<?php

namespace Tests\Unit;

use App\Services\Import\VocabularyWordValidator;
use PHPUnit\Framework\TestCase;

class VocabularyWordValidatorTest extends TestCase
{
    private VocabularyWordValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = new VocabularyWordValidator();
    }

    public function test_accepts_single_word(): void
    {
        $result = $this->validator->validate('hello');

        $this->assertTrue($result['valid']);
    }

    public function test_rejects_multi_word_phrase(): void
    {
        $result = $this->validator->validate('thank you');

        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('multi-word', $result['reason']);
    }

    public function test_rejects_sentence_with_punctuation(): void
    {
        $result = $this->validator->validate('Anna is my sister.');

        $this->assertFalse($result['valid']);
    }

    public function test_rejects_activity_title_pattern(): void
    {
        $result = $this->validator->validate('Talent Show Event of the Day');

        $this->assertFalse($result['valid']);
    }

    public function test_validates_lesson_word_count_range(): void
    {
        $this->assertTrue($this->validator->validateLessonWordCount(5)['valid']);
        $this->assertTrue($this->validator->validateLessonWordCount(10)['valid']);
        $this->assertFalse($this->validator->validateLessonWordCount(4)['valid']);
        $this->assertFalse($this->validator->validateLessonWordCount(11)['valid']);
    }
}
