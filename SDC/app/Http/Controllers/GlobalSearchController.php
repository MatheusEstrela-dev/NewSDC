<?php

namespace App\Http\Controllers;

use App\Services\GlobalSearchService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class GlobalSearchController extends Controller
{
    public function __construct(
        protected GlobalSearchService $searchService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = $request->input('query');
        
        if (empty($query) || strlen($query) < 3) {
            return response()->json([]);
        }

        $results = $this->searchService->search($query);

        return response()->json($results);
    }
}
