<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ProductListingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class ProductSearchController extends Controller
{
    public function __construct(
        private ProductListingService $products,
    ) {}

    #[OA\Get(
        path: '/api/catalog/search',
        operationId: 'catalogSearch',
        tags: ['Catalog'],
        parameters: [
            new OA\Parameter(
                name: 'q',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', maxLength: 100),
            ),
            new OA\Parameter(
                name: 'limit',
                in: 'query',
                required: false,
                schema: new OA\Schema(
                    type: 'integer',
                    minimum: 1,
                    maximum: ProductListingService::SEARCH_MAX_LIMIT,
                ),
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Search hits or empty list when query is missing or shorter than 2 characters',
                content: new OA\JsonContent(
                    required: ['data', 'meta'],
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(ref: '#/components/schemas/CatalogProduct'),
                        ),
                        new OA\Property(
                            property: 'meta',
                            required: ['limit', 'query'],
                            properties: [
                                new OA\Property(property: 'limit', type: 'integer'),
                                new OA\Property(property: 'query', type: 'string'),
                            ],
                            type: 'object',
                        ),
                    ],
                ),
            ),
            new OA\Response(
                response: 422,
                description: 'Validation failed',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiError'),
            ),
        ],
    )]
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
