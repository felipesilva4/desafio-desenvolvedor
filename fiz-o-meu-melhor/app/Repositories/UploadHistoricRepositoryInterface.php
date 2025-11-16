<?php

namespace App\Repositories;

use App\Models\UploadHistoric;
use Illuminate\Database\Eloquent\Collection;

interface UploadHistoricRepositoryInterface
{
    public function existsByHash(string $hash): bool;

    public function create(array $data): UploadHistoric;

    public function findById(int $id): ?UploadHistoric;

    public function updateStatusById(int $id, string $status): void;

    public function getUploadHistoric(): ?Collection;
}

