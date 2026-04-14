<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\CategoryDirectoryService;
use Illuminate\Http\JsonResponse;

class CategoryController extends Controller
{
    public function __construct(
        private CategoryDirectoryService $categories,
    ) {}

    public function index(): JsonResponse
    {
        return response()->json([
            'data' => $this->categories->rootCategories(),
        ]);
    }

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
