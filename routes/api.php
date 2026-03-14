<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\ProductGroupController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\StockIssueController;
use App\Http\Controllers\Api\StockReceiptController;
use App\Http\Controllers\Api\StocktakeController;
use App\Http\Controllers\Api\StockTransferController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\WarehouseController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::post('/auth/login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/auth/me', [AuthController::class, 'me']);

        Route::middleware('role:system_admin')->group(function (): void {
            Route::apiResource('users', UserController::class);
        });

        Route::middleware('role:system_admin|warehouse_staff')->group(function (): void {
            Route::apiResource('warehouses', WarehouseController::class);
            Route::apiResource('product-groups', ProductGroupController::class)
                ->parameters(['product-groups' => 'productGroup']);
            Route::apiResource('products', ProductController::class);

            Route::apiResource('stock-receipts', StockReceiptController::class)
                ->parameters(['stock-receipts' => 'stockReceipt']);
            Route::apiResource('stock-issues', StockIssueController::class)
                ->parameters(['stock-issues' => 'stockIssue']);
            Route::apiResource('stock-transfers', StockTransferController::class)
                ->parameters(['stock-transfers' => 'stockTransfer']);
            Route::apiResource('stocktakes', StocktakeController::class)
                ->parameters(['stocktakes' => 'stocktake']);

            Route::get('/reports/inventory-by-warehouse', [ReportController::class, 'inventoryByWarehouse']);
            Route::get('/reports/in-out-by-period', [ReportController::class, 'inOutByPeriod']);
            Route::get('/reports/low-stock', [ReportController::class, 'lowStock']);
            Route::get('/reports/slow-moving', [ReportController::class, 'slowMoving']);
        });
    });
});
