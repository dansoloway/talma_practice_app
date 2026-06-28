<?php

namespace App\Services\Import;

class SummerImportOptions
{
    /**
     * @param array<string, string> $vocabCsvByCefr CEFR level => CSV path
     */
    public function __construct(
        public bool $dryRun = false,
        public bool $force = false,
        public bool $translate = false,
        public bool $generateImages = false,
        public bool $generateTts = false,
        public ?string $cefr = null,
        public bool $detachFromDefault = true,
        public bool $strict = false,
        public bool $skipArchive = false,
        public array $vocabCsvByCefr = [],
        public ?string $promptsCsv = null,
    ) {}

    public function isStructureOnly(): bool
    {
        return !$this->translate && !$this->generateImages && !$this->generateTts;
    }

    public function usesValidatedVocabCsv(): bool
    {
        return $this->vocabCsvByCefr !== [];
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
            strict: (bool) ($flags['strict'] ?? false),
            skipArchive: (bool) ($flags['skip-archive'] ?? false),
            vocabCsvByCefr: self::resolveVocabCsvPaths($flags),
            promptsCsv: isset($flags['prompts-csv']) && $flags['prompts-csv'] !== ''
                ? (string) $flags['prompts-csv']
                : null,
        );
    }

    /**
     * @param array<string, mixed> $flags
     * @return array<string, string>
     */
    private static function resolveVocabCsvPaths(array $flags): array
    {
        /** @var list<string> $paths */
        $paths = $flags['vocab-csv'] ?? [];
        if (!is_array($paths)) {
            $paths = [$paths];
        }

        $cefrFilter = isset($flags['cefr']) && $flags['cefr'] !== ''
            ? self::normalizeCefr((string) $flags['cefr'])
            : null;

        $resolved = [];

        foreach ($paths as $entry) {
            $entry = trim((string) $entry);
            if ($entry === '') {
                continue;
            }

            if (str_contains($entry, '=')) {
                [$cefr, $path] = explode('=', $entry, 2);
                $cefr = self::normalizeCefr(trim($cefr));
                $resolved[$cefr] = trim($path);
                continue;
            }

            if ($cefrFilter !== null) {
                $resolved[$cefrFilter] = $entry;
                continue;
            }

            $detected = self::detectCefrFromVocabCsvPath($entry);
            if ($detected !== null) {
                $resolved[$detected] = $entry;
            }
        }

        foreach (['Pre-A1', 'A1', 'A2', 'B1'] as $cefr) {
            if (isset($resolved[$cefr])) {
                continue;
            }

            if ($cefrFilter !== null && $cefrFilter !== $cefr) {
                continue;
            }

            $conventions = [
                base_path('data/summer-vocab-' . strtolower(str_replace('-', '', $cefr)) . '.csv'),
                base_path('data/summer-vocab-pre-a1.csv'),
            ];
            if ($cefr === 'Pre-A1') {
                $conventions = [base_path('data/summer-vocab-pre-a1.csv')];
            } elseif ($cefr === 'A1') {
                $conventions = [base_path('data/summer-vocab-a1.csv')];
            } elseif ($cefr === 'A2') {
                $conventions = [
                    base_path('data/summer-vocab-a2.csv'),
                    base_path('data/A2_Vocabulary - A2_Vocabulary.csv.csv'),
                ];
            } elseif ($cefr === 'B1') {
                $conventions = [base_path('data/summer-vocab-b1.csv')];
            }

            foreach ($conventions as $convention) {
                if (is_readable($convention)) {
                    $resolved[$cefr] = $convention;
                    break;
                }
            }
        }

        return $resolved;
    }

    private static function detectCefrFromVocabCsvPath(string $path): ?string
    {
        $basename = strtolower(basename($path));
        $map = [
            'pre-a1' => 'Pre-A1',
            'prea1' => 'Pre-A1',
            'a1' => 'A1',
            'a2' => 'A2',
            'b1' => 'B1',
        ];

        foreach ($map as $needle => $cefr) {
            if (str_contains($basename, $needle)) {
                return $cefr;
            }
        }

        return null;
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
