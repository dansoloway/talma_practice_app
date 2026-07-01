<?php

namespace Tests\Unit;

use App\Services\SpeechTranscriptScorer;
use PHPUnit\Framework\TestCase;

class SpeechTranscriptScorerTest extends TestCase
{
    public function test_exact_sentence_match_passes(): void
    {
        $result = SpeechTranscriptScorer::score(
            'My favorite color is red',
            'My favorite color is red'
        );

        $this->assertTrue($result['pass']);
        $this->assertGreaterThanOrEqual(0.75, $result['ratio']);
    }

    public function test_partial_sentence_match_can_pass_with_lenient_threshold(): void
    {
        $result = SpeechTranscriptScorer::score(
            'my favorite color red',
            'My favorite color is red'
        );

        $this->assertTrue($result['pass']);
    }

    public function test_single_vocabulary_word_match_passes(): void
    {
        $result = SpeechTranscriptScorer::score('apple', 'apple');

        $this->assertTrue($result['pass']);
    }

    public function test_empty_transcript_fails(): void
    {
        $result = SpeechTranscriptScorer::score('', 'apple');

        $this->assertFalse($result['pass']);
        $this->assertSame(0.0, $result['ratio']);
    }

    public function test_unrelated_transcript_fails(): void
    {
        $result = SpeechTranscriptScorer::score('banana', 'apple');

        $this->assertFalse($result['pass']);
    }
}
