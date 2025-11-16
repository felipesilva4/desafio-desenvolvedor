<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Repositories\MongoRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

#[OA\Tag(name: "Data", description: "Endpoints para busca de dados importados")]
class SearchImportedDataController extends Controller
{
    public function __construct(
        private readonly MongoRepositoryInterface $mongoRepository,
    ) {
    }

    #[OA\Get(
        path: "/data",
        summary: "Buscar dados importados",
        description: "Busca dados importados do MongoDB filtrando por TckrSymb (ticker) e RptDt (data do relatório). Retorna apenas os campos essenciais.",
        tags: ["Data"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "TckrSymb",
                in: "query",
                required: true,
                description: "Símbolo do ticker (ex: PETR4, AMZO34)",
                schema: new OA\Schema(type: "string", example: "PETR4")
            ),
            new OA\Parameter(
                name: "RptDt",
                in: "query",
                required: true,
                description: "Data do relatório no formato YYYY-MM-DD",
                schema: new OA\Schema(type: "string", format: "date", example: "2024-01-01")
            ),
            new OA\Parameter(
                name: "page",
                in: "query",
                required: false,
                description: "Número da página (padrão: 1)",
                schema: new OA\Schema(type: "integer", minimum: 1, example: 1)
            ),
            new OA\Parameter(
                name: "per_page",
                in: "query",
                required: false,
                description: "Itens por página (padrão: 50, máximo: 500)",
                schema: new OA\Schema(type: "integer", minimum: 1, maximum: 500, example: 50)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Lista de dados encontrados",
                content: new OA\JsonContent(
                    type: "array",
                    items: new OA\Items(
                        properties: [
                            new OA\Property(property: "RptDt", type: "string", format: "date", example: "2024-08-22"),
                            new OA\Property(property: "TckrSymb", type: "string", example: "AMZO34"),
                            new OA\Property(property: "MktNm", type: "string", example: "EQUITY-CASH"),
                            new OA\Property(property: "SctyCtgyNm", type: "string", example: "BDR"),
                            new OA\Property(property: "ISIN", type: "string", example: "BRAMZOBDR002"),
                            new OA\Property(property: "CrpnNm", type: "string", example: "AMAZON.COM, INC")
                        ]
                    ),
                    example: [
                        [
                            "RptDt" => "2024-08-22",
                            "TckrSymb" => "AMZO34",
                            "MktNm" => "EQUITY-CASH",
                            "SctyCtgyNm" => "BDR",
                            "ISIN" => "BRAMZOBDR002",
                            "CrpnNm" => "AMAZON.COM, INC"
                        ]
                    ]
                )
            ),
            new OA\Response(response: 401, description: "Não autenticado"),
            new OA\Response(response: 422, description: "Erro de validação - TckrSymb e RptDt são obrigatórios")
        ]
    )]
    public function getData(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'TckrSymb' => ['required', 'string'],
            'RptDt' => ['required', 'string'],
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:500'],
        ]);

        $page = $validated['page'] ?? 1;
        $perPage = $validated['per_page'] ?? 50;
        $skip = ($page - 1) * $perPage;

        $tckrSymb = $validated['TckrSymb'];
        $rptDt = $validated['RptDt'];

        $documents = $this->mongoRepository->findByFilters($tckrSymb, $rptDt, $perPage, $skip);

        $filteredDocuments = array_map(function (array $document) {
            return [
                'RptDt' => $document['RptDt'] ?? '',
                'TckrSymb' => $document['TckrSymb'] ?? '',
                'MktNm' => $document['MktNm'] ?? '',
                'SctyCtgyNm' => $document['SctyCtgyNm'] ?? '',
                'ISIN' => $document['ISIN'] ?? '',
                'CrpnNm' => $document['CrpnNm'] ?? '',
            ];
        }, $documents);

        return response()->json($filteredDocuments);
    }
}

