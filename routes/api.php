<?php

use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\ShiprocketWebhookController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider or bootstrap/app.php
| and assigned to the "api" middleware group.
|
*/

// Shiprocket Logistics Webhook (URL: /api/logistics/webhook)
Route::match(['get', 'post'], '/logistics/webhook', [ShiprocketWebhookController::class, 'handle'])
    ->name('api.logistics.webhook');

// Product API Routes
Route::controller(ProductController::class)->group(function () {
    Route::get('/categories', [ProductController::class, 'categories']);
    Route::get('/sliders', [ProductController::class, 'sliders']);
    Route::get('/products', [ProductController::class, 'index']);
    Route::get('/{category_slug}/{slug}', [ProductController::class, 'showWithoutSubcategory']);
    Route::get('/{category_slug}/{subcategory_slug}/{slug}', [ProductController::class, 'showWithSubcategory']);
});
