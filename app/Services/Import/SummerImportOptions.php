<?php

namespace App\Services\Import;

class SummerImportOptions
{
    public function __construct(
        public bool $dryRun = false,
        public bool $force = false,
        public bool $translate = true,
        public bool $generateImages = true,
        public bool $generateTts = true,
        public ?string $cefr = null,
        public bool $detachFromDefault = true,
    ) {}

    /**
     * @param array<string, mixed> $flags
     */
    public static function fromCommandFlags(array $flags): self
    {
        return new self(
            dryRun: (bool) ($flags['dry-run'] ?? false),
            force: (bool) ($flags['force'] ?? false),
            translate: !($flags['skip-translations'] ?? false),
            generateImages: !($flags['skip-images'] ?? false),
            generateTts: !($flags['skip-tts'] ?? false),
            cefr: isset($flags['cefr']) && $flags['cefr'] !== '' ? (string) $flags['cefr'] : null,
            detachFromDefault: !($flags['no-detach-from-default'] ?? false),
        );
    }

    /**
     * @return list<string>
     */
    public function cefrLevels(): array
    {
        $all = ['Pre-A1', 'A1', 'A2', 'B1'];

        if ($this->cefr === null) {
            return $all;
        }

        $normalized = self::normalizeCefr($this->cefr);
        if (!in_array($normalized, $all, true)) {
            throw new \InvalidArgumentException("Invalid CEFR level: {$this->cefr}. Expected Pre-A1, A1, A2, or B1.");
        }

        return [$normalized];
    }

    public static function normalizeCefr(string $cefr): string
    {
        $cefr = trim($cefr);
        $map = [
            'pre-a1' => 'Pre-A1',
            'pre a1' => 'Pre-A1',
            'prea1' => 'Pre-A1',
            'a1' => 'A1',
            'a2' => 'A2',
            'b1' => 'B1',
        ];

        $key = strtolower(str_replace('_', '-', $cefr));

        return $map[$key] ?? $cefr;
    }
}
