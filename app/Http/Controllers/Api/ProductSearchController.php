<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ProductListingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductSearchController extends Controller
{
    public function __construct(
        private ProductListingService $products,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'q' => 'sometimes|string|max:100',
            'limit' => 'sometimes|integer|min:1|max:'.ProductListingService::SEARCH_MAX_LIMIT,
        ]);

        $q = trim((string) $request->query('q', ''));

        if ($q === '' || mb_strlen($q) < 2) {
            return response()->json([
                'data' => [],
                'meta' => [
                    'limit' => 0,
                    'query' => $q,
                ],
            ]);
        }

        $limit = (int) $request->query('limit', 10);
        $limit = min(ProductListingService::SEARCH_MAX_LIMIT, max(1, $limit));

        $payload = $this->products->searchByQuery($q, $limit);

        return response()->json($payload);
    }
}
