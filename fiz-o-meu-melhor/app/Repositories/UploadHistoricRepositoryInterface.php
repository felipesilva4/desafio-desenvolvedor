<?php

namespace App\Repositories;

use App\Models\UploadHistoric;

interface UploadHistoricRepositoryInterface
{
    public function existsByHash(string $hash): bool;

    public function create(array $data): UploadHistoric;

    public function findById(int $id): ?UploadHistoric;

    public function updateStatusById(int $id, string $status): void;
}

