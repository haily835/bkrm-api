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
use Intervention\Image\Facades\Image;

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
Route::post('/ownerLogin', [AuthController::class, 'ownerLogin']);
Route::post('/employeeLogin', [AuthController::class, 'employeeLogin']);
Route::resource('/barcodes', BarcodeController::class);
Route::post('/image-uploads', function(Request $request) {
    if ($request['image']) {
        $imagePath = $request['image']->store('images', 'public');

        $image = Image::make(public_path("storage/{$imagePath}"))->fit(1000, 1000);
        $image->save();

        $path = 'http://103.163.118.100/bkrm-api/storage/app/public/' . $imagePath;
        return response()->json(['message' => $path]);
    }
});


// Protected routes
Route::group(['middleware' => ['auth:user']], function () {

    Route::get('/stores', [StoreController::class, 'index']);
    Route::post('/stores', [StoreController::class, 'store']);
    Route::post('/stores/{store}', [StoreController::class, 'update']);
    Route::delete('/stores/{store}', [StoreController::class, 'destroy']); 

    Route::get('/stores/{store}/employees', [EmployeeController::class, 'index']);
    Route::post('/stores/{store}/employees', [EmployeeController::class, 'store']);
    Route::post('/employees/{employee}', [EmployeeController::class, 'update']);
    Route::delete('/employees/{employee}', [EmployeeController::class, 'destroy']); 

    Route::get('/stores/{store}/products', [ProductController::class, 'index']);
    Route::post('/stores/{store}/products', [ProductController::class, 'store']);
    Route::post('/products/{product}', [ProductController::class, 'update']);
    Route::delete('/products/{product}', [ProductController::class, 'destroy']);

    Route::get('/stores/{store}/products/{product}/product-prices', [ProductPriceController::class, 'index']);
    Route::post('/stores/{store}/products/{product}/product-prices', [ProductPriceController::class, 'store']);
    Route::post('/products/{product}/product-prices/{productPrice}', [ProductPriceController::class, 'update']);
    Route::delete('/products/{product}/product-prices/{productPrice}', [ProductPriceController::class, 'destroy']);

    Route::get('/stores/{store}/branches', [BranchController::class, 'index']);
    Route::post('/stores/{store}/branches', [BranchController::class, 'store']);
    Route::post('/branch/{branch}', [BranchController::class, 'update']);
    Route::delete('/branch/{branch}', [BranchController::class, 'destroy']);

    Route::get('/stores/{store}/branches/{branch}/orders', [OrderController::class, 'index']);
    Route::post('/stores/{store}/branches/{branch}/orders', [OrderController::class, 'store']);
    Route::post('/orders/{order}', [OrderController::class, 'update']);
    Route::delete('/orders/{order}', [OrderController::class, 'destroy']);

    Route::get('/stores/{store}/branches/{branch}/invoices', [InvoiceController::class, 'index']);
    Route::post('/stores/{store}/branches/{branch}/invoices', [InvoiceController::class, 'store']);
    Route::post('/invoices/{invoice}', [InvoiceController::class, 'update']);
    Route::delete('/invoices/{invoice}', [InvoiceController::class, 'destroy']);

    Route::get('/stores/{store}/branches/{branch}/orders/{order}/details', [OrderDetailController::class, 'index']);
    Route::post('/stores/{store}/branches/{branch}/orders/{order}/details', [OrderDetailController::class, 'store']);
    Route::post('/orders/{order}/details/{orderDetail}', [OrderDetailController::class, 'update']);
    Route::delete('/orders/{order}/details/{orderDetail}', [OrderDetailController::class, 'destroy']);

    Route::get('/stores/{store}/branches/{branch}/refunds', [RefundController::class, 'index']);
    Route::post('/stores/{store}/branches/{branch}/refunds', [RefundController::class, 'store']);
    Route::post('/refunds/{refund}', [RefundController::class, 'update']);
    Route::delete('/refunds/{refund}', [RefundController::class, 'destroy']);

    Route::get('/stores/{store}/branches/{branch}/refunds/{refund}/details', [RefundDetailController::class, 'index']);
    Route::post('/stores/{store}/branches/{branch}/refunds/{refund}/details', [RefundDetailController::class, 'store']);
    Route::post('/refunds/{refund}/details/{refundDetail}', [RefundDetailController::class, 'update']);
    Route::delete('/refunds/{refund}/details/{refundDetail}', [RefundDetailController::class, 'destroy']);

    Route::get('/stores/{store}/branches/{branch}/purchase-orders', [PurchaseOrderController::class, 'index']);
    Route::post('/stores/{store}/branches/{branch}/purchase-orders', [PurchaseOrderController::class, 'store']);
    Route::post('/purchase-orders/{purchaseOrder}', [PurchaseOrderController::class, 'update']);
    Route::delete('/purchase-orders/{purchaseOrder}', [PurchaseOrderController::class, 'destroy']);

    Route::get('/stores/{store}/branches/{branch}/purchase-orders/{purchaseOrder}/details', [PurchaseOrderDetailController::class, 'index']);
    Route::post('/stores/{store}/branches/{branch}/purchase-orders/{purchaseOrder}/details', [PurchaseOrderDetailController::class, 'store']);
    Route::post('/purchase-orders/{purchaseOrder}/details/{purchaseOrderDetail}', [PurchaseOrderDetailController::class, 'update']);
    Route::delete('/purchase-orders/{purchaseOrder}/details/{purchaseOrderDetail}', [PurchaseOrderDetailController::class, 'destroy']);

    Route::get('/stores/{store}/branches/{branch}/purchase-returns', [PurchaseReturnController::class, 'index']);
    Route::post('/stores/{store}/branches/{branch}/purchase-returns', [PurchaseReturnController::class, 'store']);
    Route::post('/branches/{branch}/purchase-returns/{purchaseReturn}', [PurchaseReturnController::class, 'update']);
    Route::delete('/branches/{branch}/purchase-returns/{purchaseReturn}', [PurchaseReturnController::class, 'destroy']);

    Route::get('/stores/{store}/branches/{branch}/purchase-returns/{purchaseReturn}/details', [PurchaseReturnnDetailController::class, 'index']);
    Route::post('/stores/{store}/branches/{branch}/purchase-returns/{purchaseReturn}/details', [PurchaseReturnnDetailController::class, 'store']);
    Route::post('/purchase-returns/{purchaseReturn}/details/{purchaseReturnDetail}', [PurchaseReturnnDetailController::class, 'update']);
    Route::delete('/purchase-returns/{purchaseReturn}/details/{purchaseReturnDetail}', [PurchaseReturnnDetailController::class, 'destroy']);

    Route::get('/stores/{store}/suppliers', [SupplierController::class, 'index']);
    Route::post('/stores/{store}/suppliers', [SupplierController::class, 'store']);
    Route::post('/suppliers/{supplier}', [SupplierController::class, 'update']);
    Route::delete('/suppliers/{supplier}', [SupplierController::class, 'destroy']);

    Route::post('/logout', [AuthController::class, 'logout']);
});
