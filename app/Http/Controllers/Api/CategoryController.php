<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\CategoryDirectoryService;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

class CategoryController extends Controller
{
    public function __construct(
        private CategoryDirectoryService $categories,
    ) {}

    #[OA\Get(
        path: '/api/catalog/categories',
        operationId: 'catalogCategoriesRoot',
        tags: ['Catalog'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Root categories',
                content: new OA\JsonContent(
                    required: ['data'],
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(ref: '#/components/schemas/CategoryNode'),
                        ),
                    ],
                ),
            ),
        ],
    )]
    public function index(): JsonResponse
    {
        return response()->json([
            'data' => $this->categories->rootCategories(),
        ]);
    }

    #[OA\Get(
        path: '/api/catalog/categories/{id}',
        operationId: 'catalogCategoryShow',
        tags: ['Catalog'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', minimum: 1),
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Child categories and breadcrumb for a category',
                content: new OA\JsonContent(
                    required: ['data', 'meta'],
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(ref: '#/components/schemas/CategoryNode'),
                        ),
                        new OA\Property(
                            property: 'meta',
                            required: ['parent', 'breadcrumb'],
                            properties: [
                                new OA\Property(
                                    property: 'parent',
                                    nullable: true,
                                    ref: '#/components/schemas/CategoryNode',
                                ),
                                new OA\Property(
                                    property: 'breadcrumb',
                                    type: 'array',
                                    items: new OA\Items(ref: '#/components/schemas/CategoryNode'),
                                ),
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
    public function show(int $id): JsonResponse
    {
        $payload = $this->categories->childrenOf($id);

        return response()->json([
            'data' => $payload['children'],
            'meta' => [
                'parent' => $payload['parent'],
                'breadcrumb' => $payload['breadcrumb'],
            ],
        ]);
    }
}
