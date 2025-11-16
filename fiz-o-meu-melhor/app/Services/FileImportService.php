<?php

namespace App\Services;

use App\Exceptions\FileAlreadyImportedException;
use App\Models\UploadHistoric;
use App\Repositories\UploadHistoricRepositoryInterface;
use App\Services\Contracts\FileImportServiceInterface;
use App\Services\Contracts\QueuesServiceInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Illuminate\Support\Facades\Schema;
use Throwable;

class FileImportService implements FileImportServiceInterface
{
    public function __construct(
        private readonly UploadHistoricRepositoryInterface $uploadHistoricRepository,
        private readonly QueuesServiceInterface $queuesService
    ) {
    }

    public function handleUpload(UploadedFile $file): UploadHistoric
    {
        $contents = file_get_contents($file->getRealPath());

        if ($contents === false) {
            throw new RuntimeException('Não foi possível ler o conteúdo do arquivo.');
        }

        $hash = md5($contents);

        if ($this->uploadHistoricRepository->existsByHash($hash)) {
            throw new FileAlreadyImportedException();
        }

        $uploadHistoric = DB::transaction(function () use ($file, $contents, $hash) {
            /** @var UploadHistoric $upload */
            $upload = $this->uploadHistoricRepository->create([
                'name' => $file->getClientOriginalName() ?: $file->getFilename(),
                'hash' => $hash,
                'reference_date' => now()->toDateString(),
                'status' => UploadHistoric::STATUS_WAITING,
            ]);

            if (Schema::hasTable('issodeviaserums3')) {
                $upload->storage()->create([
                    'file_path' => base64_encode($contents),
                ]);
            }
            return $upload;
        });

        $this->dispatchToQueue($uploadHistoric);

        return $uploadHistoric;
    }

    private function dispatchToQueue(UploadHistoric $uploadHistoric): void
    {
        try {
            $this->queuesService->dispatchToDefault([
                'upload_id' => $uploadHistoric->id,
            ]);
        } catch (Throwable $exception) {
            Log::error('Falha ao enviar upload para a fila.', [
                'upload_id' => $uploadHistoric->id,
                'error' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }
}

