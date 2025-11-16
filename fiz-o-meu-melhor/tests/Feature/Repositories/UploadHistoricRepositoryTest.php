<?php

namespace Tests\Feature\Repositories;

use App\Models\UploadHistoric;
use App\Repositories\UploadHistoricRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UploadHistoricRepositoryTest extends TestCase
{
    use RefreshDatabase;

    public function testItInteractsWithDatabase(): void
    {
        $repository = new UploadHistoricRepository();

        $upload = $repository->create([
            'name' => 'file.csv',
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

