<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\StoreController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductPriceController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\PurchaseOrderController;
use App\Http\Controllers\PurchaseOrderDetailController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\OrderDetailController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\RefundController;
use App\Http\Controllers\RefundDetailController;
use App\Http\Controllers\PurchaseReturnController;
use App\Http\Controllers\PurchaseReturnDetailController;
use App\Http\Controllers\InventoryTransactionController;
use App\Http\Controllers\BarcodeController;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::post('/register', [AuthController::class, 'register']);
Route::get('/ownerLogin', [AuthController::class, 'ownerLogin']);
Route::get('/employeeLogin', [AuthController::class, 'employeeLogin']);
Route::resource('/barcodes', BarcodeController::class);

// Protected routes
Route::group(['middleware' => ['auth:sanctum']], function () {
    Route::resource('stores', StoreController::class);
    Route::resource('branches', BranchController::class);
    Route::resource('products', ProductController::class);
    Route::resource('product-prices', ProductPriceController::class);
    Route::resource('employees', EmployeeController::class);
    Route::resource('suppliers', SupplierController::class);
    Route::resource('purchase-orders', PurchaseOrderController::class);
    Route::resource('purchase-order-details', PurchaseOrderDetailController::class);
    Route::resource('orders', OrderController::class);
    Route::resource('invoices', InvoiceController::class);
    Route::resource('order-details', OrderDetailController::class);
    Route::resource('refunds', RefundController::class);
    Route::resource('refund-details', RefundDetailController::class);
    Route::resource('purchase-returns', PurchaseReturnController::class);
    Route::resource('purchase-return-details', PurchaseReturnDetailController::class);
    Route::resource('inventory-transactions', InventoryTransactionController::class);
    Route::post('/logout', [AuthController::class, 'logout']);
});

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
