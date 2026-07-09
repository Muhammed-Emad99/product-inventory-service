<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'message' => 'Product Inventory Microservice API is running.',
    ]);
});
