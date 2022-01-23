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
use App\Http\Controllers\InventoryCheckController;
use App\Http\Controllers\StoreReportController;
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

Route::get('/address/provinces/', [AddressController::class, 'getProvinces']);
Route::get('/address/provinces/{province}/districts', [AddressController::class, 'getDistricts']);
Route::get('/address/provinces/{province}/districts/{district}/wards', [AddressController::class, 'getWards']);

Route::get('/searchDefaultProduct', [ProductController::class, 'searchDefaultProduct']);

// Protected routes
Route::group(['middleware' => ['auth:user,employee']], function () {

    Route::get('/stores', [StoreController::class, 'index']);
    Route::post('/stores', [StoreController::class, 'store']);
    Route::put('/stores/{store:uuid}', [StoreController::class, 'update']);
    Route::delete('/stores/{store:uuid}', [StoreController::class, 'destroy']);
    Route::get('/stores/{store:uuid}/activities', [StoreController::class, 'activities']); 
    
    // routes for report of store
    Route::get('/stores/{store:uuid}/report/overview', [StoreReportController::class, 'overview']); 
    Route::get('/stores/{store:uuid}/report/statistic', [StoreReportController::class, 'statistic']); 
    Route::get('/stores/{store:uuid}/report/top', [StoreReportController::class, 'getTopOfStore']); 
    Route::get('/stores/{store:uuid}/report/item', [StoreReportController::class, 'getReportItems']); 
    Route::get('/stores/{store:uuid}/report/category', [StoreReportController::class, 'getReportCategories']); 
    
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
    Route::get('/stores/{store:uuid}/branches/{branch:uuid}/products', [ProductController::class, 'indexOfBranch']);
    Route::get('/stores/{store:uuid}/search-products', [ProductController::class, 'search']);
    Route::get('/stores/{store:uuid}/branches/{branch:uuid}/search-products', [ProductController::class, 'searchBranchInventory']);
    Route::post('/stores/{store:uuid}/products', [ProductController::class, 'store']);
    Route::put('/stores/{store:uuid}/products/{product:uuid}', [ProductController::class, 'update']);
    Route::get('/stores/{store:uuid}/products/{product:uuid}', [ProductController::class, 'show']);
    Route::delete('/stores/{store:uuid}/products/{product:uuid}', [ProductController::class, 'destroy']);
    Route::post('/stores/{store:uuid}/products/{product:uuid}/active', [ProductController::class, 'active']);
    Route::post('/stores/{store:uuid}/products/{product:uuid}/inactive', [ProductController::class, 'inactive']);

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

    Route::get('/stores/{store:uuid}/branches/{branch:uuid}/orders/{order:uuid}/details', [OrderDetailController::class, 'index']);
    Route::post('/stores/{store:uuid}/branches/{branch:uuid}/orders/{order:uuid}/details', [OrderDetailController::class, 'store']);
    Route::put('/stores/{store:uuid}/branches/{branch:uuid}/orders/{order:uuid}/details/{orderDetail}', [OrderDetailController::class, 'update']);
    Route::delete('/stores/{store:uuid}/branches/{branch:uuid}/orders/{order:uuid}/details/{orderDetail}', [OrderDetailController::class, 'destroy']);

    Route::get('/stores/{store:uuid}/branches/{branch:uuid}/refunds', [RefundController::class, 'index']);
    Route::get('/stores/{store:uuid}/refunds', [RefundController::class, 'getStoreRefund']);
    Route::post('/stores/{store:uuid}/branches/{branch:uuid}/refunds', [RefundController::class, 'store']);
    Route::post('/stores/{store:uuid}/branches/{branch:uuid}/refunds/removeInventory', [RefundController::class, 'removeInventory']);
    Route::put('/stores/{store:uuid}/branches/{branch:uuid}/refunds/{refund:uuid}', [RefundController::class, 'update']);
    Route::get('/stores/{store:uuid}/refunds/{refund:uuid}', [RefundController::class, 'show']);
    Route::delete('/stores/{store:uuid}/branches/{branch:uuid}/refunds/{refund:uuid}', [RefundController::class, 'destroy']);


    Route::get('/stores/{store:uuid}/purchase-orders', [PurchaseOrderController::class, 'getStorePurchaseOrder']);
    Route::get('/stores/{store:uuid}/branches/{branch:uuid}/purchase-orders', [PurchaseOrderController::class, 'index']);
    Route::post('/stores/{store:uuid}/branches/{branch:uuid}/purchase-orders', [PurchaseOrderController::class, 'store']);
    Route::post('/stores/{store:uuid}/branches/{branch:uuid}/purchase-orders/addInventory', [PurchaseOrderController::class, 'addInventory']);
    Route::get('/stores/{store:uuid}/purchase-orders/{purchaseOrder:uuid}', [PurchaseOrderController::class, 'show']);
    Route::put('/stores/{store:uuid}/branches/{branch:uuid}/purchase-orders/{purchaseOrder:uuid}', [PurchaseOrderController::class, 'update']);
    Route::delete('/stores/{store:uuid}/branches/{branch:uuid}/purchase-orders/{purchaseOrder:uuid}', [PurchaseOrderController::class, 'destroy']);


    Route::get('/stores/{store:uuid}/purchase-returns', [PurchaseReturnController::class, 'getStorePurchaseReturn']);
    Route::get('/stores/{store:uuid}/purchase-returns/{purchaseReturn:uuid}', [PurchaseReturnController::class, 'show']);
    Route::get('/stores/{store:uuid}/branches/{branch:uuid}/purchase-returns', [PurchaseReturnController::class, 'index']);
    Route::post('/stores/{store:uuid}/branches/{branch:uuid}/purchase-returns', [PurchaseReturnController::class, 'store']);
    Route::post('/stores/{store:uuid}/branches/{branch:uuid}/purchase-returns/removeInventory', [PurchaseReturnController::class, 'removeInventory']);
    // Route::get('/stores/{store:uuid}/branches/{branch:uuid}/purchase-returns/{purchaseReturn:uuid}', [PurchaseReturnController::class, 'show']);


    Route::get('/stores/{store:uuid}/branches/{branch:uuid}/inventory-checks/{inventoryCheck:uuid}', [InventoryCheckController::class, 'show']);
    Route::get('/stores/{store:uuid}/branches/{branch:uuid}/inventory-checks', [InventoryCheckController::class, 'index']);
    Route::post('/stores/{store:uuid}/branches/{branch:uuid}/inventory-checks', [InventoryCheckController::class, 'store']);

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
    Route::get('/stores/{store:uuid}/categories/parent', [CategoryController::class, 'getParentCategory']);
    Route::post('/stores/{store:uuid}/categories', [CategoryController::class, 'store']);
    Route::get('/stores/{store:uuid}/categories/{category:uuid}', [CategoryController::class, 'show']);
    Route::put('/stores/{store:uuid}/categories/{category:uuid}', [CategoryController::class, 'update']);
    Route::delete('/stores/{store:uuid}/categories/{category:uuid}', [CategoryController::class, 'destroy']);

    Route::get('/stores/{store:uuid}/branches/{branch:uuid}/inventory', [InventoryTransactionController::class, 'index']);
    Route::post('/logout', [AuthController::class, 'logout']);
});