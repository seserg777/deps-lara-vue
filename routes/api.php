<?php

use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\CategoryProductController;
use App\Http\Controllers\Api\ProductSearchController;
use Illuminate\Support\Facades\Route;

Route::prefix('catalog')->group(function (): void {
    Route::get('search', [ProductSearchController::class, 'index']);
    Route::get('categories', [CategoryController::class, 'index']);
    Route::get('categories/{category}/products', [CategoryProductController::class, 'index'])->whereNumber('category');
    Route::get('categories/{id}', [CategoryController::class, 'show'])->whereNumber('id');
});
