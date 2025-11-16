<?php

namespace App\Repositories;

use App\Models\UploadHistoric;

class UploadHistoricRepository implements UploadHistoricRepositoryInterface
{
    public function existsByHash(string $hash): bool
    {
        return UploadHistoric::where('hash', $hash)->exists();
    }

    public function create(array $data): UploadHistoric
    {
        return UploadHistoric::create($data);
    }

    public function findById(int $id): ?UploadHistoric
    {
        return UploadHistoric::find($id);
    }

    public function updateStatusById(int $id, string $status): void
    {
        UploadHistoric::where('id', $id)->update(['status' => $status]);
    }
}

