<?php

namespace Tests\Unit\Services;

use App\Exceptions\FileAlreadyImportedException;
use App\Models\UploadHistoric;
use App\Repositories\UploadHistoricRepository;
use App\Services\Contracts\QueuesServiceInterface;
use App\Services\FileImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class FileImportServiceTest extends TestCase
{
    use RefreshDatabase;

    private UploadHistoricRepository $repository;
    private QueuesServiceInterface|MockInterface $queuesService;
    private FileImportService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = new UploadHistoricRepository();
        $this->queuesService = Mockery::mock(QueuesServiceInterface::class);
        $this->service = new FileImportService($this->repository, $this->queuesService);
    }

    public function testHandleUploadCreatesRecordAndDispatchesQueue(): void
    {
        $file = UploadedFile::fake()->createWithContent('data.csv', 'value');

        UploadHistoric::factory()->make();

        $this->queuesService
            ->shouldReceive('dispatchToDefault')
            ->once()
            ->with(Mockery::on(function (array $payload) {
                return isset($payload['upload_id']) && is_int($payload['upload_id']);
            }));

        $result = $this->service->handleUpload($file);
        $this->assertInstanceOf(UploadHistoric::class, $result);
    }

    public function testHandleUploadThrowsExceptionForDuplicateHash(): void
    {
        $file = UploadedFile::fake()->createWithContent('data.csv', 'value');
        $hash = md5('value');

        UploadHistoric::factory()->create(['hash' => $hash]);

        $this->expectException(FileAlreadyImportedException::class);

        $this->service->handleUpload($file);
    }

    public function testShouldReturnUploadHistoric(): void
    {
        $upload = UploadHistoric::factory()->create();

        print_r($upload->toArray());
        $response = $this->service->getUploadDataHistoric();

        $this->assertContains($upload->id, array_column($response, 'id'));
    }
}

