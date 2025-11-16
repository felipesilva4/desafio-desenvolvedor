<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Repositories\MongoRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchImportedDataController extends Controller
{
    public function __construct(
        private readonly MongoRepositoryInterface $mongoRepository,
    ) {
    }

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

