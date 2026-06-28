<?php

namespace App\Services\Import;

use Illuminate\Support\Facades\Log;

/**
 * Console + file logging for Summer Practice Pal imports.
 */
class SummerImportReporter
{
    private readonly string $logPath;

    private float $startedAt;

    private int $lessonTotal = 0;

    private int $lessonCurrent = 0;

    /** @var (callable(string): void)|null */
    private $consoleOutput;

    /**
     * @param (callable(string): void)|null $consoleOutput
     */
    public function __construct(
        ?callable $consoleOutput = null,
    ) {
        $this->consoleOutput = $consoleOutput;
        $this->logPath = storage_path('logs/summer_practice_pal_import.log');
        $this->startedAt = microtime(true);
    }

    public function logPath(): string
    {
        return $this->logPath;
    }

    public function setLessonTotal(int $total): void
    {
        $this->lessonTotal = max(0, $total);
        $this->lessonCurrent = 0;
    }

    public function info(string $message, array $context = []): void
    {
        $this->write('INFO', $message, $context);
    }

    public function warn(string $message, array $context = []): void
    {
        $this->write('WARN', $message, $context);
    }

    public function error(string $message, array $context = []): void
    {
        $this->write('ERROR', $message, $context);
    }

    /**
     * @param array<string, mixed> $result
     */
    public function lessonCompleted(string $cefr, string $lessonTitle, string $lessonSlug, array $result): void
    {
        $this->lessonCurrent++;

        $lessonStatus = ($result['lesson_created'] ?? false)
            ? 'created'
            : (($result['lesson_skipped'] ?? false) ? 'exists' : 'updated');

        $context = [
            'cefr' => $cefr,
            'lesson' => $lessonTitle,
            'slug' => $lessonSlug,
            'status' => $lessonStatus,
            'vocab_created' => $result['vocabulary_created'] ?? 0,
            'vocab_skipped' => $result['vocabulary_skipped'] ?? 0,
            'prompts_created' => $result['prompts_created'] ?? 0,
            'prompts_skipped' => $result['prompts_skipped'] ?? 0,
            'options_created' => $result['options_created'] ?? 0,
            'games_ensured' => (bool) ($result['games_ensured'] ?? false),
        ];

        if (($result['vocab_enrichment_errors'] ?? 0) > 0) {
            $context['enrichment_errors'] = $result['vocab_enrichment_errors'];
        }

        $progress = $this->lessonTotal > 0
            ? sprintf('[%d/%d] ', $this->lessonCurrent, $this->lessonTotal)
            : '';

        $consoleMessage = sprintf(
            '%s%s — lesson %s | +%d vocab, ~%d skipped vocab, +%d prompts, ~%d skipped prompts',
            $progress,
            $lessonTitle,
            $lessonStatus,
            $context['vocab_created'],
            $context['vocab_skipped'],
            $context['prompts_created'],
            $context['prompts_skipped'],
        );

        $this->write('LESSON', $consoleMessage, $context, $consoleMessage);
    }

    /**
     * @param array<string, mixed> $summary
     */
    public function finish(array $summary): void
    {
        $duration = round(microtime(true) - $this->startedAt, 1);
        $summary['duration_seconds'] = $duration;

        $this->write('DONE', "Import finished in {$duration}s", $summary);

        if ($this->consoleOutput !== null) {
            ($this->consoleOutput)(sprintf('Log written to: %s', $this->logPath));
        }
    }

    private function write(string $level, string $message, array $context = [], ?string $consoleMessage = null): void
    {
        $timestamp = now()->format('Y-m-d H:i:s');
        $fileLine = "[{$timestamp}] [{$level}] {$message}";

        if ($context !== []) {
            $fileLine .= ' ' . json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        @file_put_contents($this->logPath, $fileLine . PHP_EOL, FILE_APPEND | LOCK_EX);

        Log::info("SummerPracticePalImport [{$level}] {$message}", $context);

        if ($this->consoleOutput === null) {
            return;
        }

        $out = $consoleMessage ?? "[{$timestamp}] {$message}";
        ($this->consoleOutput)($out);
    }
}
