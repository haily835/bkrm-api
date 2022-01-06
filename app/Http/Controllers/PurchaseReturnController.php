<?php

namespace App\Http\Controllers;

use App\Models\PurchaseReturn;
use App\Models\Store;
use App\Models\Branch;
use Illuminate\Http\Request;
use App\Models\BranchInventory;
use App\Models\InventoryTransaction;
use App\Models\PurchaseReturnDetail;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Employee;
use Illuminate\Support\Str;

class PurchaseReturnController extends Controller
{
    public function index(Store $store, Branch $branch)
    {
        return response()->json([
            'data' => $branch->purchaseReturns,
        ], 200);
    }

    public function getStorePurchaseReturn(Store $store) {
        $data = $store->purchaseReturns()
            ->join('suppliers', 'purchase_returns.supplier_id', '=', 'suppliers.id')
            ->join('branches', 'purchase_returns.branch_id', '=', 'branches.id')
            ->select('purchase_returns.*', 'suppliers.name as supplier_name', 'branches.name as branch_name')->get();
                                    
        return response()->json([
            'data' => $data,
        ]);
    }

    public function removeInventory(Request $request, Store $store, Branch $branch) {
        $validated = $request->validate([
            'purchase_order_uuid' => 'required|string',
            'supplier_id' => 'required|numeric',
            'paid_amount' => 'required|string',
            'payment_method' => 'required|string',
            'total_amount' => 'required|string',
            'status' => 'required|string',
            'details' => 'required',
            'export_date' => 'required|date_format:Y-m-d H:i:s',
        ]);

        // get the user of  token
        $created_by = $approved_by = null;
        $created_user_type = '';

        if (Auth::guard('user')->user()) {
            $created_by = $approved_by = Auth::guard('user')->user()->id;
            $created_user_type = 'owner';
        } else if (Auth::guard('employee')->user()){
            $created_by = $approved_by = Auth::guard('employee')->user()->id;
            $created_user_type = 'employee';
        } else {
            return response()->json([
                'message' => 'Unauthorized',
            ], 401);
        }

        $creation_date = $approved_date = $validated['export_date'];

        $purchase_order_id = $store->purchaseOrders()->where('uuid',$validated['purchase_order_uuid'])->first()->id;

        $last_id = $store->purchaseReturns()->count();

        $purchaseReturnCode = 'PR' . sprintf( '%06d', $last_id );

        $purchaseReturn = PurchaseReturn::create([
            'store_id' => $store->id,
            'uuid' => (string)Str::uuid(),
            'branch_id' => $branch->id,
            'supplier_id' => $validated['supplier_id'],
            'purchase_return_code' => $purchaseReturnCode,
            'approved_by' => $approved_by,
            'created_by' => $created_by,
            'creation_date' => $creation_date,
            'approved_date' => $approved_date,
            'paid_amount' => $validated['paid_amount'],
            'payment_method' => $validated['payment_method'],
            'total_amount' => $validated['total_amount'],
            'created_user_type' => $created_user_type,
            'purchase_order_id' => $purchase_order_id,
            'status' => $validated['status'],
        ]);

        foreach ($validated['details'] as $detail) {
            $inventoryTransaction = InventoryTransaction::create([
                'uuid' => (string)Str::uuid(),
                'store_id' => $store->id,
                'product_id' => $detail['product_id'],
                'quantity' => $detail['quantity'],
                'branch_id' => $branch->id,
                'transaction_type' => 'supplier_returned',
            ]);

            PurchaseReturnDetail::create([
                'store_id' => $store->id,
                'branch_id' => $branch->id,
                'product_id' => $detail['product_id'],
                'inventory_transaction_id' => $inventoryTransaction->id,
                'purchase_return_id' => $purchaseReturn->id,
                'removed_from_inventory' => true,
                'unit_price' => $detail['unit_price'],
                'quantity' => $detail['quantity']
            ]);

            $product = $store->products->where('id', '=', $detail['product_id'])->first();
            $newQuantity = (string)((int) $product->quantity_available) - ((int) $detail['quantity']);
            $product->update(['quantity_available' => $newQuantity]);
            // $productOfStore = BranchInventory::where([['branch_id', '=', $branch->id], ['product_id', '=', $product_id]])->first();

            // if ($productOfStore) {
            //     $newQuantity = ((float) $productOfStore->quantity_available) + ((float) $detail['quantity']);
            //     $productOfStore->update(['quantity', $newQuantity]);
            // } else {
            //     BranchInventory::create([
            //         'store_id' => $store->id,
            //         'branch_id' => $branch->id,
            //         'product_id' => $product_id,
            //         'quantity_available' => $detail['quantity'],
            //     ]);
            // }
        }

        return response()->json([
            'message' => 'Purchase return created successfully',
            'data' => $purchaseReturn,
        ], 200);
    }
    public function store(Request $request, Store $store, Branch $branch)
    {
        $validated = $request->validate([
            'supplier_id' => 'required|numeric',
            'created_by' => 'required|numeric',
            'creation_date' => 'required|date_format:Y-m-d',
            'approved_by' => 'nullable|numeric',
            'approved_date' => 'nullable|date_format:Y-m-d',
            'payment_type' => 'nullable|string',
            'return_amount' => 'nullable|numeric',
            'notes' => 'nullable|text',
        ]);
        
        $purchaseReturn = PurchaseReturn::create(array_merge(
            [
                'store_id' => $store->id,
                'branch_id' => $branch->id,
            ],
            $validated
        ));


        return response()->json([
            'message' => 'Purchase return created',
            'data' => $purchaseReturn,
        ], 200);
    }

    public function show(Store $store, Branch $branch, PurchaseReturn $purchaseReturn) {
        return response()->json([
            'data' => $purchaseReturn,
        ], 200);
    }
    
    public function update(Request $request, Store $store, Branch $branch, PurchaseReturn $purchaseReturn)
    {
        $validated = $request->validate([
            'supplier_id' => 'nullable|numeric',
            'approved_by' => 'nullable|numeric',
            'approved_date' => 'nullable|date_format:Y-m-d',
            'payment_type' => 'nullable|string',
            'return_amount' => 'nullable|numeric',
            'notes' => 'nullable|text',
        ]);

        $purchaseReturn->update($validated);

        return response()->json([
            'message' => 'Purchase return updated',
            'data' => $purchaseReturn,
        ], 200);
    }

    public function destroy(Store $store, Branch $branch, PurchaseReturn $purchaseReturn)
    {
        $isdeleted = PurchaseReturn::destroy($purchaseReturn);
        return response()->json([
            'message' => $isdeleted,
            'data' => $purchaseReturn
        ], 200);
    }
}
