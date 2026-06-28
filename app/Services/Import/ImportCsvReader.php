<?php

namespace App\Services\Import;

use RuntimeException;

class ImportCsvReader
{
    /**
     * @return list<array<string, string>>
     */
    public function read(string $path): array
    {
        if (!is_readable($path)) {
            throw new RuntimeException("CSV file not found or not readable: {$path}");
        }

        $handle = fopen($path, 'r');
        if ($handle === false) {
            throw new RuntimeException("Unable to open CSV file: {$path}");
        }

        $headers = fgetcsv($handle, 0, ',', '"', '\\');
        if ($headers === false || $headers === [null]) {
            fclose($handle);

            return [];
        }

        $headers = array_map(fn ($h) => trim((string) $h), $headers);
        $rows = [];

        while (($data = fgetcsv($handle, 0, ',', '"', '\\')) !== false) {
            if ($data === [null] || $data === false) {
                continue;
            }

            $row = [];
            foreach ($headers as $index => $header) {
                if ($header === '') {
                    continue;
                }
                $row[$header] = trim((string) ($data[$index] ?? ''));
            }

            if ($this->rowIsEmpty($row)) {
                continue;
            }

            $rows[] = $row;
        }

        fclose($handle);

        return $rows;
    }

    /**
     * @param array<string, string> $row
     */
    private function rowIsEmpty(array $row): bool
    {
        foreach ($row as $value) {
            if ($value !== '') {
                return false;
            }
        }

        return true;
    }
}
