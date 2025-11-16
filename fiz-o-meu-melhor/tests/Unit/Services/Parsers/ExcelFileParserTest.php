<?php

namespace Tests\Unit\Services\Parsers;

use App\Services\Parsers\ExcelFileParser;
use Exception;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PHPUnit\Framework\TestCase;

class ExcelFileParserTest extends TestCase
{
    public function testParsesValidExcel(): void
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray([
            ['RptDt', 'TckrSymb', 'MktNm'],
            ['2024-08-23', 'ZZZZ11', 'EQUITY-CASH'],
        ]);

        $contents = $this->exportSpreadsheet($spreadsheet);

        $parser = new ExcelFileParser();
        $result = $parser->parse($contents);

        $this->assertSame([
            ['RptDt' => '2024-08-23', 'TckrSymb' => 'ZZZZ11', 'MktNm' => 'EQUITY-CASH'],
        ], $result);
    }

    public function testThrowsWhenRequiredColumnsMissing(): void
    {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getActiveSheet()->fromArray([
            ['Foo', 'Bar'],
            ['1', '2'],
        ]);

        $parser = new ExcelFileParser();

        $this->expectException(Exception::class);
        $parser->parse($this->exportSpreadsheet($spreadsheet));
    }

    private function exportSpreadsheet(Spreadsheet $spreadsheet): string
    {
        $writer = new Xlsx($spreadsheet);
        $temp = tempnam(sys_get_temp_dir(), 'xlsx_');
        $writer->save($temp);
        $contents = file_get_contents($temp);
        @unlink($temp);

        return $contents ?: '';
    }
}

