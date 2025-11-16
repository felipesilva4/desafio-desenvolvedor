<?php

namespace Tests\Unit\Repositories;

use App\Models\UploadHistoric;
use App\Repositories\UploadHistoricRepository;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class UploadHistoricRepositoryTest extends TestCase
{
    private UploadHistoricRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        DB::beginTransaction();
        $this->repository = new UploadHistoricRepository();
    }

    protected function tearDown(): void
    {
        DB::rollBack();
        parent::tearDown();
    }

    public function testItChecksIfHashExists(): void
    {
        UploadHistoric::factory()->create(['hash' => 'abc123']);

        $this->assertTrue($this->repository->existsByHash('abc123'));
        $this->assertFalse($this->repository->existsByHash('other-hash'));
    }

    public function testItCreatesANewUploadHistoric(): void
    {
        $data = UploadHistoric::factory()->make()->only([
            'name',
            'file_path',
            'hash',
            'reference_date',
            'status',
        ]);

        $created = $this->repository->create($data);

        $this->assertDatabaseHas('upload_historics', [
            'id' => $created->id,
            'hash' => $data['hash'],
        ]);
    }

    public function testItFindsUploadById(): void
    {
        $upload = UploadHistoric::factory()->create();

        $found = $this->repository->findById($upload->id);

        $this->assertNotNull($found);
        $this->assertSame($upload->id, $found->id);
        $this->assertNull($this->repository->findById(999999));
    }
}

