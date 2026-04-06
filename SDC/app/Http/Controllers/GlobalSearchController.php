<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\GlobalSearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GlobalSearchController extends Controller
{
    public function __construct(private readonly GlobalSearchService $searchService)
    {
    }

    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'q' => ['required', 'string', 'min:3', 'max:100'],
        ]);

        $query = $request->string('q')->toString();

        return response()->json([
            'query'   => $query,
            'results' => $this->searchService->search($query),
        ]);
    }
}
