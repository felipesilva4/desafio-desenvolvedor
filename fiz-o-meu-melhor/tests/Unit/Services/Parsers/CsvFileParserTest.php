<?php

namespace Tests\Unit\Services\Parsers;

use App\Services\Parsers\CsvFileParser;
use Exception;
use PHPUnit\Framework\TestCase;

class CsvFileParserTest extends TestCase
{
    public function testParsesValidCsv(): void
    {
        $parser = new CsvFileParser();
        $csv = <<<CSV
            Status do Arquivo: Final
            RptDt;TckrSymb;MktNm
            2024-08-23;ABCD11;EQUITY-CASH
            CSV;

        $result = $parser->parse($csv);

        $this->assertSame([
            ['RptDt' => '2024-08-23', 'TckrSymb' => 'ABCD11', 'MktNm' => 'EQUITY-CASH'],
        ], $result);
    }

    public function testThrowsWhenRequiredColumnsMissing(): void
    {
        $parser = new CsvFileParser();
        $csv = "Other;Column\nvalue;123";

        $this->expectException(Exception::class);
        $parser->parse($csv);
    }
}

