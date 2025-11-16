<?php

namespace Tests\Feature\Repositories;

use App\Models\UploadHistoric;
use App\Repositories\UploadHistoricRepository;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class UploadHistoricRepositoryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        DB::beginTransaction();
    }

    protected function tearDown(): void
    {
        DB::rollBack();
        parent::tearDown();
    }

    public function testItInteractsWithDatabase(): void
    {
        $repository = new UploadHistoricRepository();

        $upload = $repository->create([
            'name' => 'file.csv',
            'file_path' => base64_encode('content'),
            'hash' => 'hash-123',
            'reference_date' => now()->toDateString(),
            'status' => UploadHistoric::STATUS_WAITING,
        ]);

        $this->assertNotNull($upload->id);
        $this->assertTrue($repository->existsByHash('hash-123'));
        $this->assertDatabaseHas('upload_historics', [
            'id' => $upload->id,
            'name' => 'file.csv',
        ]);
    }
}

