<?php

namespace App\Repositories;

use MongoDB\Client;
use MongoDB\Collection;
use MongoDB\Model\BSONDocument;

class MongoRepository implements MongoRepositoryInterface
{
    private string $database;

    private string $collection;

    public function __construct(private readonly Client $client)
    {
        $config = config('mongo');
        $this->database = $config['database'] ?? 'file_imports';
        $this->collection = $config['collection'] ?? 'upload_rows';
    }

    public function insertMany(array $documents): void
    {
        if (empty($documents)) {
            return;
        }

        $this->collection()->insertMany($documents);
    }

    public function findByFilters(string $tckrSymb, string $rptDt, int $limit = 50, int $skip = 0): array
    {
        $cursor = $this->collection()->find(
            [
                'TckrSymb' => $tckrSymb,
                'RptDt' => $rptDt,
            ],
            [
                'limit' => $limit,
                'skip' => $skip,
                'sort' => ['_id' => 1],
            ]
        );

        $documents = [];

        foreach ($cursor->toArray() as $document) {
            if ($document instanceof BSONDocument) {
                $document = $document->getArrayCopy();
            } else {
                $document = (array) $document;
            }

            if (isset($document['_id'])) {
                $document['_id'] = (string) $document['_id'];
            }

            $documents[] = $document;
        }

        return $documents;
    }

    private function collection(): Collection
    {
        return $this->client
            ->selectDatabase($this->database)
            ->selectCollection($this->collection);
    }
}

