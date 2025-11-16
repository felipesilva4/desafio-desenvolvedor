<?php

namespace App\Services\Contracts;

use App\Models\UploadHistoric;
use Illuminate\Http\UploadedFile;

interface FileImportServiceInterface
{
    public function handleUpload(UploadedFile $file): UploadHistoric;

    public function getUploadDataHistoric(): array;
}

