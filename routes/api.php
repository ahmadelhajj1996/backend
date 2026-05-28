<?php
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AttributeController;
use App\Http\Controllers\AttributeOptionController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CharacteristicController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\VariationAttributeController;
use App\Http\Controllers\VariationController;
use App\Http\Controllers\VariationImageController;
use App\Http\Controllers\ExchangeRateController;

use Illuminate\Support\Facades\Route;

Route::prefix('admin')->group(function () {
    Route::post('login', [AdminController::class, 'login']);
    Route::middleware('auth:admin')->group(function () {
        Route::post('logout', [AdminController::class, 'logout']);
        Route::post('refresh', [AdminController::class, 'refresh']);
        Route::get('me', [AdminController::class, 'me']);
        Route::get('/notifications', [NotificationController::class, 'index']);
        Route::post('/notifications/read', [NotificationController::class, 'markAllAsRead']);
        Route::delete('/notifications/{id}/delete', [NotificationController::class, 'destroy']);

    });
});

Route::get('exchange-rates', [ExchangeRateController::class, 'index']);

Route::post(
    'exchange-rates/update-prices',
    [ExchangeRateController::class, 'updatePrices']
);

Route::prefix('client')->group(function () {
    Route::post('register', [ClientController::class, 'register']);
    Route::post('login', [ClientController::class, 'login']);
    Route::middleware('auth:client')->group(function () {
        Route::post('logout', [ClientController::class, 'logout']);
        Route::post('refresh', [ClientController::class, 'refresh']);
        Route::get('me', [ClientController::class, 'me']);
    });
});

Route::get('clients', [ClientController::class, 'index']);

Route::apiResource('orders', OrderController::class);

Route::apiResource('messages', MessageController::class);

Route::apiResource('characteristics', CharacteristicController::class);

Route::apiResource('categories', CategoryController::class);

Route::apiResource('products', ProductController::class)->parameters([
    'products' => 'product',
]);

Route::get('featured_products', [ProductController::class, 'featured']);

Route::prefix('products')->group(function () {
    Route::put('{product}/restore', [ProductController::class, 'restore']);
    Route::delete('{product}/force-delete', [ProductController::class, 'forceDelete']);
    Route::patch('{product}/status', [ProductController::class, 'updateStatus']);
    Route::patch('{product}/stock', [ProductController::class, 'updateStock']);

});

Route::apiResource('attributes', AttributeController::class);

Route::apiResource('attributes-options', AttributeOptionController::class);

Route::apiResource('variations', VariationController::class);
Route::apiResource('variations-attributes', VariationAttributeController::class);

Route::apiResource('variation-images', VariationImageController::class);

Route::get(
    'variation-images/variation/{variationId}',
    [VariationImageController::class, 'getByVariation']
);
Route::post(
    '/variation-images/sync',
    [VariationImageController::class, 'sync']
);

Route::get("/search", [SearchController::class, "index"]);
