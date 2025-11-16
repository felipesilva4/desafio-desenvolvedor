<?php

namespace App\Services\Parsers;

use Exception;

class CsvFileParser extends AbstractFileParser
{
    public function supports(string $extension): bool
    {
        return strtolower($extension) === 'csv';
    }

    public function parse(string $contents): array
    {
        $contents = str_replace("\r", "\n", $contents);
        $contents = preg_replace("/\n+/", "\n", $contents);
        $lines = explode("\n", trim((string) $contents));

        $header = null;
        $documents = [];

        foreach ($lines as $line) {
            $line = trim((string) $line);

            if ($line === '') {
                continue;
            }

            $line = preg_replace('/^\xEF\xBB\xBF/', '', $line);
            $normalizedStatus = mb_strtoupper($line ?? '', 'UTF-8');

            if ($line === '' || str_starts_with($normalizedStatus, 'STATUS DO ARQUIVO')) {
                continue;
            }

            $delimiter = $this->detectDelimiter($line);

            $columns = str_getcsv($line, $delimiter, '"', '\\');

            if ($header === null) {
                $candidate = $this->normalizeHeaderCandidate($columns);

                try {
                    $this->ensureRequiredColumns($candidate);
                } catch (Exception $exception) {
                    continue;
                }

                $header = $candidate;
                continue;
            }
            $documents[] = $this->combineRow($header, $columns);
        }

        if ($header === null) {
            throw new Exception('Arquivo inválido: colunas TckrSymb e RptDt são obrigatórias.');
        }


        return $documents;
    }

    private function detectDelimiter(string $line): string
    {
        if (str_contains($line, ';')) {
            return ';';
        }

        if (str_contains($line, ',')) {
            return ',';
        }

        return ';';
    }
}

