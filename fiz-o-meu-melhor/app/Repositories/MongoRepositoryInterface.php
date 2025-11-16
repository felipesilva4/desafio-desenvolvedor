<?php

namespace App\Repositories;

interface MongoRepositoryInterface
{
    /**
     * @param array<int, array<string, mixed>> $documents
     */
    public function insertMany(array $documents): void;

    /**
     * @param string $tckrSymb
     * @param string $rptDt
     * @return array<int, array<string, mixed>>
     */
    public function findByFilters(string $tckrSymb, string $rptDt, int $limit = 50, int $skip = 0): array;
}

