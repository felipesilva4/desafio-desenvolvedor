<?php

namespace Tests\Feature\Services;

use App\Models\UploadHistoric;
use App\Services\Contracts\QueuesServiceInterface;
use App\Services\FileImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Mockery;
use Tests\TestCase;

class FileImportServiceTest extends TestCase
{
    use RefreshDatabase;

    public function testItPersistsUploadAndDispatchesQueue(): void
    {
        $queueMock = Mockery::mock(QueuesServiceInterface::class);
        $this->instance(QueuesServiceInterface::class, $queueMock);

        $service = $this->app->make(FileImportService::class);
        $file = UploadedFile::fake()->createWithContent('input.csv', 'first;second');

        $queueMock
            ->shouldReceive('dispatchToDefault')
            ->once()
            ->with(Mockery::on(function (array $payload) {
                $this->assertArrayHasKey('upload_id', $payload);
                return true;
            }));

        $upload = $service->handleUpload($file);

        $this->assertDatabaseHas('upload_historics', [
            'id' => $upload->id,
            'hash' => md5('first;second'),
            'status' => UploadHistoric::STATUS_WAITING,
        ]);
    }
}

