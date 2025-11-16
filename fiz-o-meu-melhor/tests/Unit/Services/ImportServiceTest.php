<?php

namespace Tests\Unit\Services;

use App\Models\UploadHistoric;
use App\Repositories\UploadHistoricRepositoryInterface;
use App\Services\ImportService;
use App\Services\Parsers\FileParserInterface;
use App\Services\Parsers\FileParserResolverInterface;
use Exception;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class ImportServiceTest extends TestCase
{
    private UploadHistoricRepositoryInterface|MockInterface $repository;
    private FileParserResolverInterface|MockInterface $resolver;
    private FileParserInterface|MockInterface $parser;
    private ImportService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = Mockery::mock(UploadHistoricRepositoryInterface::class);
        $this->resolver = Mockery::mock(FileParserResolverInterface::class);
        $this->parser = Mockery::mock(FileParserInterface::class);
        $this->service = new ImportService($this->repository, $this->resolver);
    }

    public function testHandleReturnsDocumentsFromParser(): void
    {
        $upload = UploadHistoric::factory()->make([
            'id' => 10,
            'name' => 'data.csv',
            'hash' => 'hash123',
        ]);
        // Simula relação storage com file_path codificado
        $upload->setRelation('storage', (object) ['file_path' => base64_encode('csv-content')]);

        $parsedDocuments = [
            ['TckrSymb' => 'AAA', 'RptDt' => '2024-08-23'],
        ];

        $this->repository
            ->shouldReceive('findById')
            ->once()
            ->with(1)
            ->andReturn($upload);

        $this->resolver
            ->shouldReceive('resolve')
            ->once()
            ->with('csv')
            ->andReturn($this->parser);

        $this->parser
            ->shouldReceive('parse')
            ->once()
            ->with('csv-content')
            ->andReturn($parsedDocuments);

        $result = $this->service->handle(1);

        $this->assertSame([
            ['TckrSymb' => 'AAA', 'RptDt' => '2024-08-23', 'upload_id' => 'hash123'],
        ], $result);
    }

    public function testHandleThrowsWhenUploadMissing(): void
    {
        $this->repository
            ->shouldReceive('findById')
            ->once()
            ->with(1)
            ->andReturnNull();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Upload não encontrado para importação.');

        $this->service->handle(1);
    }

    public function testHandleThrowsWhenDecodeFails(): void
    {
        $upload = UploadHistoric::factory()->make([
            'name' => 'file.csv',
        ]);
        $upload->setRelation('storage', (object) ['file_path' => '***invalid***']);

        $this->repository
            ->shouldReceive('findById')
            ->once()
            ->with(1)
            ->andReturn($upload);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Falha ao decodificar arquivo durante importação.');

        $this->service->handle(1);
    }

    public function testHandleThrowsWhenExtensionMissing(): void
    {
        $upload = UploadHistoric::factory()->make([
            'name' => null,
        ]);
        $upload->setRelation('storage', (object) ['file_path' => base64_encode('content')]);

        $this->repository
            ->shouldReceive('findById')
            ->once()
            ->with(1)
            ->andReturn($upload);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Não foi possível identificar o tipo do arquivo.');

        $this->service->handle(1);
    }
}

