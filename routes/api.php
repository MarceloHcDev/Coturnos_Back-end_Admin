<?php


use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Admin\StockController as AdminStockController;
use Illuminate\Support\Facades\Route;

// Resultado: /api/v1/admin/...
Route::prefix('v1/admin')
    ->name('admin.')
    ->middleware(['auth:sanctum', 'admin'])
    ->group(function () {

        // Produtos (sem DELETE: apenas inativar/reativar)
        Route::get('products', [AdminProductController::class, 'index'])->name('products.index');
        Route::post('products', [AdminProductController::class, 'store'])->name('products.store');
        Route::get('products/{product}', [AdminProductController::class, 'show'])->name('products.show');
        Route::match(['put', 'patch'], 'products/{product}', [AdminProductController::class, 'update'])->name('products.update');
        Route::patch('products/{product}/activate', [AdminProductController::class, 'activate'])->name('products.activate');
        Route::patch('products/{product}/deactivate', [AdminProductController::class, 'deactivate'])->name('products.deactivate');

        // Estoque
        Route::get('stock', [AdminStockController::class, 'index'])->name('stock.index');
        Route::get('products/{product}/stock', [AdminStockController::class, 'show'])->name('stock.show');
        Route::get('products/{product}/stock/movements', [AdminStockController::class, 'movements'])->name('stock.movements');
        Route::post('products/{product}/stock/movements', [AdminStockController::class, 'move'])->name('stock.move');
        Route::patch('products/{product}/stock/{stock}', [AdminStockController::class, 'update'])->name('stock.update');
    });