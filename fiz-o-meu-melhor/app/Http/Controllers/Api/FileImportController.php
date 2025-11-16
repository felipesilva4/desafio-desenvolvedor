<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\FileAlreadyImportedException;
use App\Http\Controllers\Controller;
use App\Services\Contracts\FileImportServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use OpenApi\Attributes as OA;

#[OA\Info(
    version: "1.0.0",
    title: "File Import API",
    description: "API para importação e gerenciamento de arquivos CSV/Excel"
)]
#[OA\Server(
    url: "http://localhost:8080/api",
    description: "API Server"
)]
#[OA\SecurityScheme(
    securityScheme: "bearerAuth",
    type: "http",
    name: "Authorization",
    in: "header",
    scheme: "bearer",
    bearerFormat: "JWT"
)]
#[OA\Tag(name: "Uploads", description: "Endpoints para gerenciamento de uploads de arquivos")]
class FileImportController extends Controller
{
    public function __construct(
        private readonly FileImportServiceInterface $fileImportService
    ) {
    }

    #[OA\Post(
        path: "/uploads",
        summary: "Upload de arquivo CSV/Excel",
        description: "Envia um arquivo CSV ou Excel para importação. O arquivo é validado e processado em background.",
        tags: ["Uploads"],
        security: [["bearerAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: "multipart/form-data",
                schema: new OA\Schema(
                    required: ["file"],
                    properties: [
                        new OA\Property(
                            property: "file",
                            type: "string",
                            format: "binary",
                            description: "Arquivo CSV ou Excel (.csv, .xls, .xlsx)"
                        )
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "Arquivo enviado com sucesso",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "id", type: "integer", example: 1),
                        new OA\Property(property: "name", type: "string", example: "data.csv"),
                        new OA\Property(property: "hash", type: "string", example: "abc123def456"),
                        new OA\Property(property: "status", type: "string", example: "WAITING"),
                        new OA\Property(property: "reference_date", type: "string", format: "date", example: "2024-01-01")
                    ]
                )
            ),
            new OA\Response(response: 401, description: "Não autenticado"),
            new OA\Response(response: 409, description: "Arquivo já foi importado anteriormente"),
            new OA\Response(response: 422, description: "Erro de validação")
        ]
    )]
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

    #[OA\Get(
        path: "/uploads",
        summary: "Listar histórico de uploads",
        description: "Retorna a lista de todos os uploads realizados",
        tags: ["Uploads"],
        security: [["bearerAuth" => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: "Lista de uploads",
                content: new OA\JsonContent(
                    type: "array",
                    items: new OA\Items(
                        properties: [
                            new OA\Property(property: "id", type: "integer", example: 1),
                            new OA\Property(property: "name", type: "string", example: "data.csv"),
                            new OA\Property(property: "hash", type: "string", example: "abc123"),
                            new OA\Property(property: "status", type: "string", example: "PROCESSED"),
                            new OA\Property(property: "reference_date", type: "string", format: "date", example: "2024-01-01")
                        ]
                    )
                )
            ),
            new OA\Response(response: 401, description: "Não autenticado")
        ]
    )]
    public function history(): JsonResponse
    {
        $uploadHistoric = $this->fileImportService->getUploadDataHistoric();
        
        return response()->json($uploadHistoric);
    }

}

