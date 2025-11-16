<?php

namespace Tests\Unit\Repositories;

use App\Models\UploadHistoric;
use App\Repositories\UploadHistoricRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UploadHistoricRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private UploadHistoricRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new UploadHistoricRepository();
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

    public function testShouldReturnUploadHistoric(): void
    {
        UploadHistoric::factory()->count(3)->create();
        $uploads = $this->repository->getUploadHistoric();

        $this->assertCount(3, $uploads);
        $this->assertInstanceOf(UploadHistoric::class, $uploads->first());
    }
}

