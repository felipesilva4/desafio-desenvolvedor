<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\FileAlreadyImportedException;
use App\Http\Controllers\Controller;
use App\Models\UploadHistoric;
use App\Services\Contracts\FileImportServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class FileImportController extends Controller
{
    public function __construct(
        private readonly FileImportServiceInterface $fileImportService
    ) {
    }

    public function upload(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'file' => ['required', 'file'],
        ]);

        try {
            $uploadHistoric = $this->fileImportService->handleUpload($request->file('file'));
        } catch (FileAlreadyImportedException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], Response::HTTP_CONFLICT);
        }

        return response()->json([
            'id' => $uploadHistoric->id,
            'name' => $uploadHistoric->name,
            'hash' => $uploadHistoric->hash,
            'status' => $uploadHistoric->status,
            'reference_date' => $uploadHistoric->reference_date,
        ], Response::HTTP_CREATED);
    }

    /**
     * Histórico de uploads.
     */
    public function history(): Response
    {
        return response()->noContent(Response::HTTP_NOT_IMPLEMENTED);
    }

    /**
     * Buscar conteúdo do arquivo.
     */
    public function show(UploadHistoric $uploadHistoric): Response
    {
        return response()->noContent(Response::HTTP_NOT_IMPLEMENTED);
    }
}

