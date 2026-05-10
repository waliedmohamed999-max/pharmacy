<?php

use App\Http\Controllers\Api\ClientAppController;
use Illuminate\Support\Facades\Route;

Route::prefix('mobile')->middleware('throttle:mobile-api')->name('api.mobile.')->group(function () {
    Route::get('home', [ClientAppController::class, 'home'])->name('home');
    Route::get('categories', [ClientAppController::class, 'categories'])->name('categories');
    Route::get('products', [ClientAppController::class, 'products'])->name('products');
    Route::get('products/{product:slug}', [ClientAppController::class, 'product'])->name('products.show');
    Route::post('orders', [ClientAppController::class, 'storeOrder'])->name('orders.store');
    Route::get('orders', [ClientAppController::class, 'orders'])->name('orders.index');
    Route::get('orders/{order}', [ClientAppController::class, 'order'])->name('orders.show');
});
