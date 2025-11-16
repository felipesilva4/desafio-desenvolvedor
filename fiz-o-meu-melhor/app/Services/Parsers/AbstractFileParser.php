<?php

namespace App\Services\Parsers;

use Exception;

abstract class AbstractFileParser implements FileParserInterface
{
    private const REQUIRED_COLUMNS = ['TckrSymb', 'RptDt'];

    /**
     * @param array<int, string> $headers
     */
    protected function ensureRequiredColumns(array $headers): void
    {
        $normalized = array_map('trim', $headers);

        $missing = array_filter(self::REQUIRED_COLUMNS, static fn (string $column) => !in_array($column, $normalized, true));

        if (!empty($missing)) {
            throw new Exception('Arquivo inválido: colunas TckrSymb e RptDt são obrigatórias.');
        }
    }

    /**
     * @param array<int, string> $headers
     * @param array<int, string|null> $values
     *
     * @return array<string, mixed>
     */
    protected function combineRow(array $headers, array $values): array
    {
        $document = [];

        foreach ($headers as $index => $header) {
            $headerName = trim($header);

            if ($headerName === '') {
                continue;
            }

            $document[$headerName] = $values[$index] ?? null;
        }

        return $document;
    }

    protected function normalizeHeaderCandidate(array $columns): array
    {
        if ($columns === []) {
            return [];
        }

        $columns[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $columns[0]);

        return array_map(static fn (?string $value) => trim((string) $value), $columns);
    }
}

