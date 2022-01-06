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
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\AddressController;
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


Route::post('/register', [AuthController::class, 'ownerRegister']);
Route::post('/login', [AuthController::class, 'login']);
Route::get('/verify-token', [AuthController::class, 'verifyOwnerToken']);
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

Route::get('/address/provinces/', [AddressController::class, 'getProvinces']);
Route::get('/address/provinces/{province}/districts', [AddressController::class, 'getDistricts']);
Route::get('/address/provinces/{province}/districts/{district}/wards', [AddressController::class, 'getWards']);


// Protected routes
Route::group(['middleware' => ['auth:user']], function () {

    Route::get('/stores', [StoreController::class, 'index']);
    Route::post('/stores', [StoreController::class, 'store']);
    Route::put('/stores/{store:uuid}', [StoreController::class, 'update']);
    Route::delete('/stores/{store:uuid}', [StoreController::class, 'destroy']); 

    Route::get('/stores/{store:uuid}/employees', [EmployeeController::class, 'index']);
    Route::post('/stores/{store:uuid}/employees', [EmployeeController::class, 'store']);
    Route::get('/stores/{store:uuid}/employees/{employee:uuid}', [EmployeeController::class, 'show']);
    Route::put('/stores/{store:uuid}/employees/{employee:uuid}', [EmployeeController::class, 'update']);
    Route::delete('/stores/{store:uuid}/employees/{employee:uuid}', [EmployeeController::class, 'destroy']); 
    Route::post('/stores/{store:uuid}/employees/{employee:uuid}/permissions', [EmployeeController::class, 'permissions']);
    Route::get('/stores/{store:uuid}/employees/{employee:uuid}/permissions', [EmployeeController::class, 'getEmpPermissions']);

    Route::get('/stores/{store:uuid}/products/{product:uuid}/product-prices', [ProductPriceController::class, 'index']);
    Route::post('/stores/{store:uuid}/products/{product:uuid}/product-prices', [ProductPriceController::class, 'store']);
    Route::put('/stores/{store:uuid}/products/{product:uuid}/product-prices/{productPrice}', [ProductPriceController::class, 'update']);
    Route::delete('/stores/{store:uuid}/products/{product:uuid}/product-prices/{productPrice}', [ProductPriceController::class, 'destroy']);

    Route::get('/stores/{store:uuid}/products/{product:uuid}/suppliers', [ProductController::class, 'suppliers']);
    Route::post('/stores/{store:uuid}/products/{product:uuid}/suppliers', [ProductController::class, 'addSupplier']);
    Route::delete('/stores/{store:uuid}/products/{product:uuid}/suppliers/{supplier}', [ProductController::class, 'deleteSupplier']);

    Route::get('/stores/{store:uuid}/products', [ProductController::class, 'index']);
    Route::get('/stores/{store:uuid}/search-products', [ProductController::class, 'search']);
    Route::post('/stores/{store:uuid}/products', [ProductController::class, 'store']);
    Route::put('/stores/{store:uuid}/products/{product:uuid}', [ProductController::class, 'update']);
    Route::get('/stores/{store:uuid}/products/{product:uuid}', [ProductController::class, 'show']);
    Route::delete('/stores/{store:uuid}/products/{product:uuid}', [ProductController::class, 'destroy']);


    Route::get('/stores/{store:uuid}/branches', [BranchController::class, 'index']);
    Route::post('/stores/{store:uuid}/branches', [BranchController::class, 'store']);
    Route::get('/stores/{store:uuid}/branches/{branch:uuid}', [BranchController::class, 'show']);
    Route::put('/stores/{store:uuid}/branches/{branch:uuid}', [BranchController::class, 'update']);
    Route::delete('/stores/{store:uuid}/branches/{branch:uuid}', [BranchController::class, 'destroy']);

    Route::get('/stores/{store:uuid}/branches/{branch:uuid}/orders', [OrderController::class, 'index']);
    Route::get('/stores/{store:uuid}/orders', [OrderController::class, 'getStoreOrder']);
    Route::post('/stores/{store:uuid}/branches/{branch:uuid}/orders', [OrderController::class, 'store']);
    Route::post('/stores/{store:uuid}/branches/{branch:uuid}/orders/addOrder', [OrderController::class, 'addOrder']);
    Route::get('/stores/{store:uuid}/orders/{order:uuid}', [OrderController::class, 'show']);
    Route::put('/stores/{store:uuid}/branches/{branch:uuid}/orders/{order:uuid}', [OrderController::class, 'update']);
    Route::delete('/stores/{store:uuid}/branches/{branch:uuid}/orders/{order:uuid}', [OrderController::class, 'destroy']);

    Route::get('/stores/{store:uuid}/branches/{branch:uuid}/invoices', [InvoiceController::class, 'index']);
    Route::post('/stores/{store:uuid}/branches/{branch:uuid}/invoices', [InvoiceController::class, 'store']);
    Route::get('/stores/{store:uuid}/branches/{branch:uuid}/invoices/{invoice:uuid}', [InvoiceController::class, 'show']);
    Route::put('/stores/{store:uuid}/branches/{branch:uuid}/invoices/{invoice:uuid}', [InvoiceController::class, 'update']);
    Route::delete('/stores/{store:uuid}/branches/{branch:uuid}/invoices/{invoice:uuid}', [InvoiceController::class, 'destroy']);

    Route::get('/stores/{store:uuid}/branches/{branch:uuid}/orders/{order:uuid}/details', [OrderDetailController::class, 'index']);
    Route::post('/stores/{store:uuid}/branches/{branch:uuid}/orders/{order:uuid}/details', [OrderDetailController::class, 'store']);
    Route::put('/stores/{store:uuid}/branches/{branch:uuid}/orders/{order:uuid}/details/{orderDetail}', [OrderDetailController::class, 'update']);
    Route::delete('/stores/{store:uuid}/branches/{branch:uuid}/orders/{order:uuid}/details/{orderDetail}', [OrderDetailController::class, 'destroy']);

    Route::get('/stores/{store:uuid}/branches/{branch:uuid}/refunds', [RefundController::class, 'index']);
    Route::get('/stores/{store:uuid}/refunds', [RefundController::class, 'getStoreRefund']);
    Route::post('/stores/{store:uuid}/branches/{branch:uuid}/refunds', [RefundController::class, 'store']);
    Route::post('/stores/{store:uuid}/branches/{branch:uuid}/refunds/removeInventory', [RefundController::class, 'removeInventory']);
    Route::put('/stores/{store:uuid}/branches/{branch:uuid}/refunds/{refund:uuid}', [RefundController::class, 'update']);
    Route::get('/stores/{store:uuid}/branches/{branch:uuid}/refunds/{refund:uuid}', [RefundController::class, 'show']);
    Route::delete('/stores/{store:uuid}/branches/{branch:uuid}/refunds/{refund:uuid}', [RefundController::class, 'destroy']);

    Route::get('/stores/{store:uuid}/branches/{branch:uuid}/refunds/{refund:uuid}/details', [RefundDetailController::class, 'index']);
    Route::post('/stores/{store:uuid}/branches/{branch:uuid}/refunds/{refund:uuid}/details', [RefundDetailController::class, 'store']);
    Route::put('/stores/{store:uuid}/branches/{branch:uuid}/refunds/{refund:uuid}/details/{refundDetail}', [RefundDetailController::class, 'update']);
    Route::delete('/stores/{store:uuid}/branches/{branch:uuid}/refunds/{refund:uuid}/details/{refundDetail}', [RefundDetailController::class, 'destroy']);

    Route::get('/stores/{store:uuid}/purchase-orders', [PurchaseOrderController::class, 'getStorePurchaseOrder']);
    Route::get('/stores/{store:uuid}/branches/{branch:uuid}/purchase-orders', [PurchaseOrderController::class, 'index']);
    Route::post('/stores/{store:uuid}/branches/{branch:uuid}/purchase-orders', [PurchaseOrderController::class, 'store']);
    Route::post('/stores/{store:uuid}/branches/{branch:uuid}/purchase-orders/addInventory', [PurchaseOrderController::class, 'addInventory']);
    Route::get('/stores/{store:uuid}/purchase-orders/{purchaseOrder:uuid}', [PurchaseOrderController::class, 'show']);
    Route::put('/stores/{store:uuid}/branches/{branch:uuid}/purchase-orders/{purchaseOrder:uuid}', [PurchaseOrderController::class, 'update']);
    Route::delete('/stores/{store:uuid}/branches/{branch:uuid}/purchase-orders/{purchaseOrder:uuid}', [PurchaseOrderController::class, 'destroy']);

    Route::get('/stores/{store:uuid}/branches/{branch:uuid}/purchase-orders/{purchaseOrder:uuid}/details', [PurchaseOrderDetailController::class, 'index']);
    Route::post('/stores/{store:uuid}/branches/{branch:uuid}/purchase-orders/{purchaseOrder:uuid}/details', [PurchaseOrderDetailController::class, 'store']);
    Route::put('/stores/{store:uuid}/branches/{branch:uuid}/purchase-orders/{purchaseOrder:uuid}/details/{purchaseOrderDetail}', [PurchaseOrderDetailController::class, 'update']);
    Route::delete('/stores/{store:uuid}/branches/{branch:uuid}/purchase-orders/{purchaseOrder:uuid}/details/{purchaseOrderDetail}', [PurchaseOrderDetailController::class, 'destroy']);

    Route::get('/stores/{store:uuid}/purchase-returns', [PurchaseReturnController::class, 'getStorePurchaseReturn']);
    Route::get('/stores/{store:uuid}/branches/{branch:uuid}/purchase-returns', [PurchaseReturnController::class, 'index']);
    Route::post('/stores/{store:uuid}/branches/{branch:uuid}/purchase-returns', [PurchaseReturnController::class, 'store']);
    Route::post('/stores/{store:uuid}/branches/{branch:uuid}/purchase-returns/removeInventory', [PurchaseReturnController::class, 'removeInventory']);
    Route::get('/stores/{store:uuid}/branches/{branch:uuid}/purchase-returns/{purchaseReturn:uuid}', [PurchaseReturnController::class, 'show']);
    Route::put('/stores/{store:uuid}/branches/{branch:uuid}/purchase-returns/{purchaseReturn:uuid}', [PurchaseReturnController::class, 'update']);
    Route::delete('/stores/{store:uuid}/branches/{branch:uuid}/purchase-returns/{purchaseReturn:uuid}', [PurchaseReturnController::class, 'destroy']);

    Route::get('/stores/{store:uuid}/branches/{branch:uuid}/purchase-returns/{purchaseReturn:uuid}/details', [PurchaseReturnDetailController::class, 'index']);
    Route::post('/stores/{store:uuid}/branches/{branch:uuid}/purchase-returns/{purchaseReturn:uuid}/details', [PurchaseReturnDetailController::class, 'store']);
    Route::put('/stores/{store:uuid}/branches/{branch:uuid}/purchase-returns/{purchaseReturn:uuid}/details/{purchaseReturnDetail}', [PurchaseReturnDetailController::class, 'update']);
    Route::delete('/stores/{store:uuid}/branches/{branch:uuid}/purchase-returns/{purchaseReturn:uuid}/details/{purchaseReturnDetail}', [PurchaseReturnDetailController::class, 'destroy']);

    Route::get('/stores/{store:uuid}/suppliers', [SupplierController::class, 'index']);
    Route::post('/stores/{store:uuid}/suppliers', [SupplierController::class, 'store']);
    Route::get('/stores/{store:uuid}/suppliers/{supplier:uuid}', [SupplierController::class, 'show']);
    Route::put('/stores/{store:uuid}/suppliers/{supplier:uuid}', [SupplierController::class, 'update']);
    Route::delete('/stores/{store:uuid}/suppliers/{supplier:uuid}', [SupplierController::class, 'destroy']);

    Route::get('/stores/{store:uuid}/customers', [CustomerController::class, 'index']);
    Route::post('/stores/{store:uuid}/customers', [CustomerController::class, 'store']);
    Route::get('/stores/{store:uuid}/customers/{customer:uuid}', [CustomerController::class, 'show']);
    Route::put('/stores/{store:uuid}/customers/{customer:uuid}', [CustomerController::class, 'update']);
    Route::delete('/stores/{store:uuid}/customers/{customer:uuid}', [CustomerController::class, 'destroy']);

    Route::get('/stores/{store:uuid}/categories', [CategoryController::class, 'index']);
    Route::post('/stores/{store:uuid}/categories', [CategoryController::class, 'store']);
    Route::get('/stores/{store:uuid}/categories/{category:uuid}', [CategoryController::class, 'show']);
    Route::put('/stores/{store:uuid}/categories/{category:uuid}', [CategoryController::class, 'update']);
    Route::delete('/stores/{store:uuid}/categories/{category:uuid}', [CategoryController::class, 'destroy']);

    Route::get('stores/{store:uuid}/branches/{branch:uuid}/inventory', [InventoryTransactionController::class, 'index']);
    Route::post('/ownerLogout', [AuthController::class, 'logout']);
});



// employee routes
Route::post('/employee/logout', [AuthController::class, 'logout'])->middleware(['auth:employee']);

Route::group(['middleware' => ['auth:employee', 'permission:manage-employees']], function () {
    Route::get('/employee/stores/{store:uuid}/employees', [EmployeeController::class, 'index']);
    Route::post('/employee/stores/{store:uuid}/employees', [EmployeeController::class, 'store']);
    Route::put('/employee/employees/{employee:uuid}', [EmployeeController::class, 'update']);
    Route::delete('/employee/employees/{employee:uuid}', [EmployeeController::class, 'destroy']); 
});

Route::group(['middleware' => ['auth:employee', 'permission:manage-orders']], function () {
    Route::get('/employee/stores/{store:uuid}/branches/{branch:uuid}/orders', [OrderController::class, 'index']);
    Route::post('/employee/stores/{store:uuid}/branches/{branch:uuid}/orders', [OrderController::class, 'store']);
    Route::put('/employee/orders/{order:uuid}', [OrderController::class, 'update']);
    Route::delete('/employee/orders/{order:uuid}', [OrderController::class, 'destroy']);

    Route::get('/employee/stores/{store}/branches/{branch}/invoices', [InvoiceController::class, 'index']);
    Route::post('/employee/stores/{store}/branches/{branch}/invoices', [InvoiceController::class, 'store']);
    Route::put('/employee/invoices/{invoice}', [InvoiceController::class, 'update']);
    Route::delete('/employee/invoices/{invoice}', [InvoiceController::class, 'destroy']);

    Route::get('/employee/stores/{store}/branches/{branch}/orders/{order}/details', [OrderDetailController::class, 'index']);
    Route::post('/employee/stores/{store}/branches/{branch}/orders/{order}/details', [OrderDetailController::class, 'store']);
    Route::put('/employee/orders/{order}/details/{orderDetail}', [OrderDetailController::class, 'update']);
    Route::delete('/employee/orders/{order}/details/{orderDetail}', [OrderDetailController::class, 'destroy']);

    Route::get('/employee/stores/{store}/branches/{branch}/refunds', [RefundController::class, 'index']);
    Route::post('/employee/stores/{store}/branches/{branch}/refunds', [RefundController::class, 'store']);
    Route::put('/employee/refunds/{refund}', [RefundController::class, 'update']);
    Route::delete('/employee/refunds/{refund}', [RefundController::class, 'destroy']);
});

Route::group(['middleware' => ['auth:employee', 'permission:manage-purchase-orders']], function () {
    Route::get('/employee/stores/{store}/branches/{branch}/purchase-orders', [PurchaseOrderController::class, 'index']);
    Route::post('/employee/stores/{store}/branches/{branch}/purchase-orders', [PurchaseOrderController::class, 'store']);
    Route::put('/employee/purchase-orders/{purchaseOrder}', [PurchaseOrderController::class, 'update']);
    Route::delete('/employee/purchase-orders/{purchaseOrder}', [PurchaseOrderController::class, 'destroy']);

    Route::get('/employee/stores/{store}/branches/{branch}/purchase-orders/{purchaseOrder}/details', [PurchaseOrderDetailController::class, 'index']);
    Route::post('/employee/stores/{store}/branches/{branch}/purchase-orders/{purchaseOrder}/details', [PurchaseOrderDetailController::class, 'store']);
    Route::put('/employee/purchase-orders/{purchaseOrder}/details/{purchaseOrderDetail}', [PurchaseOrderDetailController::class, 'update']);
    Route::delete('/employee/purchase-orders/{purchaseOrder}/details/{purchaseOrderDetail}', [PurchaseOrderDetailController::class, 'destroy']);

});

Route::group(['middleware' => ['auth:employee', 'permission:manage-purchase-returns']], function () {
    Route::get('/employee/stores/{store}/branches/{branch}/purchase-returns', [PurchaseReturnController::class, 'index']);
    Route::post('/employee/stores/{store}/branches/{branch}/purchase-returns', [PurchaseReturnController::class, 'store']);
    Route::put('/employee/branches/{branch}/purchase-returns/{purchaseReturn}', [PurchaseReturnController::class, 'update']);
    Route::delete('/employee/branches/{branch}/purchase-returns/{purchaseReturn}', [PurchaseReturnController::class, 'destroy']);

    Route::get('/employee/stores/{store}/branches/{branch}/purchase-returns/{purchaseReturn}/details', [PurchaseReturnnDetailController::class, 'index']);
    Route::post('/employee/stores/{store}/branches/{branch}/purchase-returns/{purchaseReturn}/details', [PurchaseReturnnDetailController::class, 'store']);
    Route::put('/employee/purchase-returns/{purchaseReturn}/details/{purchaseReturnDetail}', [PurchaseReturnnDetailController::class, 'update']);
    Route::delete('/employee/purchase-returns/{purchaseReturn}/details/{purchaseReturnDetail}', [PurchaseReturnnDetailController::class, 'destroy']);
});