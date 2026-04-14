<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ProductListingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoryProductController extends Controller
{
    public function __construct(
        private ProductListingService $products,
    ) {}

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
