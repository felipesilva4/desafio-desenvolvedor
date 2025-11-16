<?php

namespace Tests\Feature\Controllers\Api;

use App\Repositories\MongoRepositoryInterface;
use Tests\TestCase;

class SearchImportedDataControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\PHPOpenSourceSaver\JWTAuth\Http\Middleware\Authenticate::class);
    }

    public function testItReturnsDocumentsWithPagination(): void
    {
        $repository = $this->mock(MongoRepositoryInterface::class);
        $repository->shouldReceive('findByFilters')
            ->once()
            ->with('PETR4', '2024-01-01', 1, 1)
            ->andReturn([
                [
                    '_id' => 'doc-1',
                    'TckrSymb' => 'PETR4',
                    'RptDt' => '2024-01-01',
                    'MktNm' => 'EQUITY-CASH',
                    'SctyCtgyNm' => 'BDR',
                    'ISIN' => 'BRPETRBDR002',
                    'CrpnNm' => 'PETROBRAS',
                ],
            ]);

        $response = $this->getJson("/api/data?TckrSymb=PETR4&RptDt=2024-01-01&page=2&per_page=1");

        $response->assertOk()
            ->assertJson([
                [
                    'RptDt' => '2024-01-01',
                    'TckrSymb' => 'PETR4',
                    'MktNm' => 'EQUITY-CASH',
                    'SctyCtgyNm' => 'BDR',
                    'ISIN' => 'BRPETRBDR002',
                    'CrpnNm' => 'PETROBRAS',
                ],
            ]);
    }

    public function testItFiltersByTckrSymbAndRptDt(): void
    {
        $repository = $this->mock(MongoRepositoryInterface::class);
        $repository->shouldReceive('findByFilters')
            ->once()
            ->with('PETR4', '2024-01-01', 50, 0)
            ->andReturn([
                [
                    '_id' => 'doc-1',
                    'TckrSymb' => 'PETR4',
                    'RptDt' => '2024-01-01',
                    'MktNm' => 'EQUITY-CASH',
                    'SctyCtgyNm' => 'BDR',
                    'ISIN' => 'BRPETRBDR002',
                    'CrpnNm' => 'PETROBRAS',
                ],
            ]);

        $response = $this->getJson("/api/data?TckrSymb=PETR4&RptDt=2024-01-01");

        $response->assertOk()
            ->assertJson([
                [
                    'RptDt' => '2024-01-01',
                    'TckrSymb' => 'PETR4',
                    'MktNm' => 'EQUITY-CASH',
                    'SctyCtgyNm' => 'BDR',
                    'ISIN' => 'BRPETRBDR002',
                    'CrpnNm' => 'PETROBRAS',
                ],
            ]);
    }

    public function testItReturnsOnlyRequiredFields(): void
    {
        $repository = $this->mock(MongoRepositoryInterface::class);
        $repository->shouldReceive('findByFilters')
            ->once()
            ->with('AMZO34', '2024-08-22', 50, 0)
            ->andReturn([
                [
                    '_id' => 'doc-1',
                    'TckrSymb' => 'AMZO34',
                    'RptDt' => '2024-08-22',
                    'MktNm' => 'EQUITY-CASH',
                    'SctyCtgyNm' => 'BDR',
                    'ISIN' => 'BRAMZOBDR002',
                    'CrpnNm' => 'AMAZON.COM, INC',
                    'SomeOtherField' => 'should not appear',
                ],
            ]);

        $response = $this->getJson("/api/data?TckrSymb=AMZO34&RptDt=2024-08-22");

        $response->assertOk()
            ->assertJson([
                [
                    'RptDt' => '2024-08-22',
                    'TckrSymb' => 'AMZO34',
                    'MktNm' => 'EQUITY-CASH',
                    'SctyCtgyNm' => 'BDR',
                    'ISIN' => 'BRAMZOBDR002',
                    'CrpnNm' => 'AMAZON.COM, INC',
                ],
            ]);

        $data = $response->json();
        $this->assertArrayNotHasKey('_id', $data[0]);
        $this->assertArrayNotHasKey('SomeOtherField', $data[0]);
    }

    public function testItRequiresTckrSymbAndRptDt(): void
    {
        $response = $this->getJson("/api/data");

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['TckrSymb', 'RptDt']);
    }

    public function testItValidatesPaginationParameters(): void
    {
        $response = $this->getJson("/api/data?TckrSymb=PETR4&RptDt=2024-01-01&per_page=0");

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['per_page']);
    }
}

