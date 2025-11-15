<?php

namespace Tests\Unit\Repositories;

use App\Repositories\MongoUploadRepository;
use Mockery;
use MongoDB\Client;
use MongoDB\Collection;
use MongoDB\Database;
use Tests\TestCase;

class MongoUploadRepositoryTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function testInsertManyPersistsDocuments(): void
    {
        config()->set('mongo', [
            'database' => 'custom_db',
            'collection' => 'custom_collection',
        ]);

        $documents = [['foo' => 'bar']];

        $collection = Mockery::mock(Collection::class);
        $collection->shouldReceive('insertMany')
            ->once()
            ->with($documents);

        $database = Mockery::mock(Database::class);
        $database->shouldReceive('selectCollection')
            ->once()
            ->with('custom_collection')
            ->andReturn($collection);

        $client = Mockery::mock(Client::class);
        $client->shouldReceive('selectDatabase')
            ->once()
            ->with('custom_db')
            ->andReturn($database);

        $repository = new MongoUploadRepository($client);

        $repository->insertMany($documents);

        $this->addToAssertionCount(1);
    }

    public function testInsertManySkipsWhenDocumentsAreEmpty(): void
    {
        $client = Mockery::mock(Client::class);
        $client->shouldNotReceive('selectDatabase');

        $repository = new MongoUploadRepository($client);

        $repository->insertMany([]);

        $this->addToAssertionCount(1);
    }
}

