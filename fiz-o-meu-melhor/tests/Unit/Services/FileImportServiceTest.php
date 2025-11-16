<?php

namespace Tests\Unit\Services;

use App\Exceptions\FileAlreadyImportedException;
use App\Models\UploadHistoric;
use App\Repositories\UploadHistoricRepository;
use App\Services\Contracts\QueuesServiceInterface;
use App\Services\FileImportService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\UploadedFile;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class FileImportServiceTest extends TestCase
{
    private UploadHistoricRepository $repository;
    private QueuesServiceInterface|MockInterface $queuesService;
    private FileImportService $service;

    protected function setUp(): void
    {
        parent::setUp();
        DB::beginTransaction();

        $this->repository = new UploadHistoricRepository();
        $this->queuesService = Mockery::mock(QueuesServiceInterface::class);
        $this->service = new FileImportService($this->repository, $this->queuesService);
    }

    protected function tearDown(): void
    {
        DB::rollBack();
        parent::tearDown();
    }

    public function testHandleUploadCreatesRecordAndDispatchesQueue(): void
    {
        $file = UploadedFile::fake()->createWithContent('data.csv', 'value');
        $hash = md5('value');

        $upload = UploadHistoric::factory()->make();

        // nenhum stub do repositório: usaremos o real

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
}

