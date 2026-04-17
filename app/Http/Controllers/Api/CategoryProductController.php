<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ProductListingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class CategoryProductController extends Controller
{
    public function __construct(
        private ProductListingService $products,
    ) {}

    #[OA\Get(
        path: '/api/catalog/categories/{category}/products',
        operationId: 'catalogCategoryProducts',
        tags: ['Catalog'],
        parameters: [
            new OA\Parameter(
                name: 'category',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', minimum: 1),
            ),
            new OA\Parameter(
                name: 'page',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer', minimum: 1, default: 1),
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Paginated products in category',
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
                            required: ['current_page', 'per_page', 'has_more'],
                            properties: [
                                new OA\Property(property: 'current_page', type: 'integer'),
                                new OA\Property(property: 'per_page', type: 'integer'),
                                new OA\Property(property: 'has_more', type: 'boolean'),
                            ],
                            type: 'object',
                        ),
                    ],
                ),
            ),
            new OA\Response(
                response: 404,
                description: 'Category not found',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiError'),
            ),
        ],
    )]
    public function index(Request $request, int $category): JsonResponse
    {
        $page = (int) $request->query('page', 1);
        if ($page < 1) {
            $page = 1;
        }

        $payload = $this->products->productsInCategory($category, $page);

        return response()->json($payload);
    }
}
