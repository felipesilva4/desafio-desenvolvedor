<?php

namespace App\Services\Parsers;

use Exception;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ExcelFileParser extends AbstractFileParser
{
    public function supports(string $extension): bool
    {
        return in_array(strtolower($extension), ['xlsx', 'xlsm', 'xls'], true);
    }

    public function parse(string $contents): array
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'upload_excel_');

        if ($tempFile === false) {
            throw new Exception('Não foi possível processar o arquivo Excel.');
        }

        file_put_contents($tempFile, $contents);

        try {
            $spreadsheet = IOFactory::load($tempFile);
        } catch (Exception $exception) {
            @unlink($tempFile);
            throw $exception;
        }

        @unlink($tempFile);

        $sheet = $spreadsheet->getActiveSheet();

        return $this->parseWorksheet($sheet);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function parseWorksheet(Worksheet $sheet): array
    {
        $rows = $sheet->toArray(null, true, true, true);

        $header = null;
        $documents = [];

        foreach ($rows as $row) {
            $values = array_values($row);

            $isEmpty = array_reduce($values, static fn (bool $carry, $value) => $carry && ($value === null || trim((string) $value) === ''), true);

            if ($isEmpty) {
                continue;
            }

            if ($header === null) {
                $candidate = $this->normalizeHeaderCandidate($values);

                try {
                    $this->ensureRequiredColumns($candidate);
                } catch (Exception $exception) {
                    continue;
                }

                $header = $candidate;
                continue;
            }

            $documents[] = $this->combineRow($header, $values);
        }

        if ($header === null) {
            throw new Exception('Arquivo inválido: colunas TckrSymb e RptDt são obrigatórias.');
        }

        return $documents;
    }
}

