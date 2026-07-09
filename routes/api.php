<?php

use App\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider or application bootstrap.
|
*/

Route::middleware('throttle:60,1')->group(function () {
    // List low-stock products (defined before /{id} to prevent path resolution conflicts)
    Route::get('/products/low-stock', [ProductController::class, 'lowStock']);

    // Standard REST CRUD endpoints for Products
    Route::get('/products', [ProductController::class, 'index']);
    Route::get('/products/{id}', [ProductController::class, 'show']);
    Route::post('/products', [ProductController::class, 'store']);
    Route::put('/products/{id}', [ProductController::class, 'update']);
    Route::delete('/products/{id}', [ProductController::class, 'destroy']);

    // Adjust Stock Levels (increment/decrement)
    Route::post('/products/{id}/stock', [ProductController::class, 'adjustStock']);
});
