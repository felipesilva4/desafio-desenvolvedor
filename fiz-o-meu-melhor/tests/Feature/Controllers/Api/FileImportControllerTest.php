<?php

namespace Tests\Feature\Controllers\Api;

use App\Exceptions\FileAlreadyImportedException;
use App\Models\UploadHistoric;
use App\Services\Contracts\FileImportServiceInterface;
use Illuminate\Http\Response;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class FileImportControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Disable JWT middleware for these tests
        $this->withoutMiddleware(\PHPOpenSourceSaver\JWTAuth\Http\Middleware\Authenticate::class);
        DB::beginTransaction();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        DB::rollBack();
        parent::tearDown();
    }

    public function testUploadReturnsCreatedResponseWithValidFile(): void
    {
        $file = UploadedFile::fake()->createWithContent('data.csv', 'content');
        $upload = UploadHistoric::factory()->make([
            'id' => 1,
            'name' => 'data.csv',
            'hash' => 'abc123',
            'status' => 'pending',
            'reference_date' => '2024-01-01',
        ]);

        $fileImportService = Mockery::mock(FileImportServiceInterface::class);
        $fileImportService->shouldReceive('handleUpload')
            ->once()
            ->with($file)
            ->andReturn($upload);

        $this->app->instance(FileImportServiceInterface::class, $fileImportService);

        $response = $this->postJson('/api/uploads', [
            'file' => $file,
        ]);

        $response->assertStatus(Response::HTTP_CREATED)
            ->assertJson([
                'id' => 1,
                'name' => 'data.csv',
                'hash' => 'abc123',
                'status' => 'pending',
                'reference_date' => '2024-01-01T00:00:00.000000Z',
            ]);
    }

    public function testUploadReturnsConflictWhenFileAlreadyImported(): void
    {
        $file = UploadedFile::fake()->createWithContent('data.csv', 'content');

        $fileImportService = Mockery::mock(FileImportServiceInterface::class);
        $fileImportService->shouldReceive('handleUpload')
            ->once()
            ->with($file)
            ->andThrow(new FileAlreadyImportedException('Arquivo já importado'));

        $this->app->instance(FileImportServiceInterface::class, $fileImportService);

        $response = $this->postJson('/api/uploads', [
            'file' => $file,
        ]);

        $response->assertStatus(Response::HTTP_CONFLICT)
            ->assertJson([
                'message' => 'Arquivo já importado',
            ]);
    }

    public function testUploadValidatesRequiredFile(): void
    {
        $response = $this->postJson('/api/uploads', []);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['file']);
    }

    public function testHistoryReturnsNotImplemented(): void
    {
        $response = $this->getJson('/api/uploads');

        $response->assertStatus(Response::HTTP_NOT_IMPLEMENTED);
    }

    public function testShowReturnsNotImplemented(): void
    {
        $upload = UploadHistoric::factory()->create();

        $response = $this->getJson("/api/uploads/{$upload->id}");

        $response->assertStatus(Response::HTTP_NOT_IMPLEMENTED);
    }
}
