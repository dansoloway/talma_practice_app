<?php

namespace App\Services\Import;

class SummerImportOptions
{
    public function __construct(
        public bool $dryRun = false,
        public bool $force = false,
        public bool $translate = false,
        public bool $generateImages = false,
        public bool $generateTts = false,
        public ?string $cefr = null,
        public bool $detachFromDefault = true,
    ) {}

    public function isStructureOnly(): bool
    {
        return !$this->translate && !$this->generateImages && !$this->generateTts;
    }

    /**
     * @param array<string, mixed> $flags
     */
    public static function fromCommandFlags(array $flags): self
    {
        $withEnrichment = (bool) ($flags['with-enrichment'] ?? false);

        return new self(
            dryRun: (bool) ($flags['dry-run'] ?? false),
            force: (bool) ($flags['force'] ?? false),
            translate: $withEnrichment && !($flags['skip-translations'] ?? false),
            generateImages: $withEnrichment && !($flags['skip-images'] ?? false),
            generateTts: $withEnrichment && !($flags['skip-tts'] ?? false),
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
