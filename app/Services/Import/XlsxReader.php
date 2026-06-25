<?php

namespace App\Services\Import;

use RuntimeException;
use ZipArchive;

/**
 * Lightweight XLSX reader using ZipArchive (no external dependencies).
 */
class XlsxReader
{
    private ZipArchive $zip;

    /** @var array<int, string> */
    private array $sharedStrings = [];

    /** @var array<string, string> sheet name (lower) => worksheet path inside zip */
    private array $sheetPaths = [];

    public function __construct(private string $filePath)
    {
        if (!is_readable($filePath)) {
            throw new RuntimeException("XLSX file not readable: {$filePath}");
        }

        $this->zip = new ZipArchive();
        if ($this->zip->open($filePath) !== true) {
            throw new RuntimeException("Unable to open XLSX file: {$filePath}");
        }

        $this->loadSharedStrings();
        $this->loadSheetPaths();
    }

    public function __destruct()
    {
        if (isset($this->zip)) {
            $this->zip->close();
        }
    }

    /**
     * @return list<string>
     */
    public function sheetNames(): array
    {
        return array_keys($this->sheetPaths);
    }

    /**
     * Read a sheet as rows of associative arrays keyed by header row.
     *
     * @return list<array<string, string>>
     */
    public function readSheet(string $sheetName): array
    {
        $path = $this->resolveSheetPath($sheetName);
        $xml = $this->zip->getFromName($path);
        if ($xml === false) {
            throw new RuntimeException("Unable to read worksheet: {$sheetName}");
        }

        $sheet = simplexml_load_string($xml);
        if ($sheet === false) {
            throw new RuntimeException("Unable to parse worksheet XML: {$sheetName}");
        }

        $sheet->registerXPathNamespace('m', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');

        $rows = [];
        foreach ($sheet->xpath('//m:sheetData/m:row') ?: [] as $row) {
            $rows[] = $this->parseRow($row);
        }

        if ($rows === []) {
            return [];
        }

        $headers = array_map(fn ($h) => trim((string) $h), $rows[0]);
        $result = [];

        for ($i = 1, $count = count($rows); $i < $count; $i++) {
            $assoc = [];
            foreach ($headers as $colIndex => $header) {
                if ($header === '') {
                    continue;
                }
                $assoc[$header] = trim((string) ($rows[$i][$colIndex] ?? ''));
            }
            if ($this->rowHasData($assoc)) {
                $result[] = $assoc;
            }
        }

        return $result;
    }

    private function loadSharedStrings(): void
    {
        $xml = $this->zip->getFromName('xl/sharedStrings.xml');
        if ($xml === false) {
            return;
        }

        $root = simplexml_load_string($xml);
        if ($root === false) {
            return;
        }

        $root->registerXPathNamespace('m', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');

        foreach ($root->xpath('//m:si') ?: [] as $si) {
            $si->registerXPathNamespace('m', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
            $parts = [];
            foreach ($si->xpath('.//m:t') ?: [] as $t) {
                $parts[] = (string) $t;
            }
            $this->sharedStrings[] = implode('', $parts);
        }
    }

    private function loadSheetPaths(): void
    {
        $workbookXml = $this->zip->getFromName('xl/workbook.xml');
        $relsXml = $this->zip->getFromName('xl/_rels/workbook.xml.rels');
        if ($workbookXml === false || $relsXml === false) {
            throw new RuntimeException('Invalid XLSX: missing workbook metadata');
        }

        $workbook = simplexml_load_string($workbookXml);
        $rels = simplexml_load_string($relsXml);
        if ($workbook === false || $rels === false) {
            throw new RuntimeException('Invalid XLSX: unable to parse workbook metadata');
        }

        $workbook->registerXPathNamespace('m', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
        $workbook->registerXPathNamespace('r', 'http://schemas.openxmlformats.org/officeDocument/2006/relationships');
        $rels->registerXPathNamespace('r', 'http://schemas.openxmlformats.org/package/2006/relationships');

        $relMap = [];
        foreach ($rels->xpath('//r:Relationship') ?: [] as $rel) {
            $relMap[(string) $rel['Id']] = (string) $rel['Target'];
        }

        foreach ($workbook->xpath('//m:sheets/m:sheet') ?: [] as $sheet) {
            $name = strtolower(trim((string) $sheet['name']));
            $relId = (string) $sheet->attributes('r', true)['id'];
            $target = $relMap[$relId] ?? null;
            if ($target !== null) {
                $this->sheetPaths[$name] = 'xl/' . ltrim($target, '/');
            }
        }
    }

    private function resolveSheetPath(string $sheetName): string
    {
        $key = strtolower(trim($sheetName));
        if (!isset($this->sheetPaths[$key])) {
            throw new RuntimeException("Sheet not found: {$sheetName}");
        }

        return $this->sheetPaths[$key];
    }

    /**
     * @return list<string>
     */
    private function parseRow(\SimpleXMLElement $row): array
    {
        $row->registerXPathNamespace('m', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');

        $cells = [];
        foreach ($row->xpath('m:c') ?: [] as $cell) {
            $ref = (string) $cell['r'];
            $colLetters = preg_replace('/\d+/', '', $ref) ?: 'A';
            $colIndex = $this->columnLettersToIndex($colLetters);
            $cells[$colIndex] = $this->cellValue($cell);
        }

        if ($cells === []) {
            return [];
        }

        $maxIndex = max(array_keys($cells));
        $values = [];
        for ($i = 0; $i <= $maxIndex; $i++) {
            $values[] = $cells[$i] ?? '';
        }

        return $values;
    }

    private function cellValue(\SimpleXMLElement $cell): string
    {
        $type = (string) ($cell['t'] ?? '');
        $cell->registerXPathNamespace('m', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
        $valueNode = $cell->xpath('m:v');
        $raw = isset($valueNode[0]) ? (string) $valueNode[0] : '';

        if ($type === 's') {
            return $this->sharedStrings[(int) $raw] ?? '';
        }

        if ($type === 'inlineStr') {
            $inline = $cell->xpath('m:is/m:t');
            return isset($inline[0]) ? (string) $inline[0] : '';
        }

        return $raw;
    }

    private function columnLettersToIndex(string $letters): int
    {
        $letters = strtoupper($letters);
        $index = 0;
        $len = strlen($letters);
        for ($i = 0; $i < $len; $i++) {
            $index = $index * 26 + (ord($letters[$i]) - ord('A') + 1);
        }

        return $index - 1;
    }

    /**
     * @param array<string, string> $row
     */
    private function rowHasData(array $row): bool
    {
        foreach ($row as $value) {
            if (trim($value) !== '') {
                return true;
            }
        }

        return false;
    }
}
