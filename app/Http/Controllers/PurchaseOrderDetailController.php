<?php

namespace App\Http\Controllers;

use App\Models\PurchaseOrderDetail;
use App\Models\InventoryTransaction;
use App\Models\PurchaseOrder;
use App\Models\Branch;
use App\Models\Store;
use Illuminate\Http\Request;


class PurchaseOrderDetailController extends Controller
{
    public function index(Store $store, Branch $branch, PurchaseOrder $purchaseOrder)
    {
        return response()->json([
            'data' => $purchaseOrder->purchaseOrderDetails,
        ], 200);
    }

    public function store(Request $request, Store $store, Branch $branch, PurchaseOrder $purchaseOrder)
    {
        $validated = $request->validate([
            'product_id' => 'required|numeric',
            'quantity' => 'required|numeric',
            'unit_cost' => 'required|numeric',
            'date_received' => 'required|date_format:Y-m-d',
            'posted_to_inventory' => 'required|boolean',
        ]);
        
        $purchaseOrderDetail = PurchaseOrderDetail::create(array_merge(
            $validated,
            [
                'store_id' => $store->id,
                'branch_id' => $branch->id,
                'purchase_order_id' => $purchaseOrder->id
            ]
            )
        );

        return response()->json([
            'data' => $purchaseOrderDetail,
        ], 200);
    }

    public function update(Request $request, PurchaseOrder $purchaseOrder, PurchaseOrderDetail $purchaseOrderDetail)
    {
        $validated = $request->validate([
            'quantity' => 'nullable|numeric',
            'unit_price' => 'nullable|numeric',
            'date_received' => 'nullable|date_format:Y-m-d',
            'posted_to_inventory' => 'nullable|boolean',
        ]);
        
        if ($request['posted_to_inventory']) {
            if($request['posted_to_inventory'] == true) {
                $newTransaction = InventoryTransaction::create([
                    'store_id' => $request['store_id'],
                    'product_id' => $request['product_id'],
                    'branch_id' => $request['branch_id'],
                    'purchase_order_id' => $request['purchase_order_id'],
                    'quantity' => $request['quantity'],
                    'transaction_type' => 'purchased'
                ]);

                $data['inventory_transaction_id'] =  $newTransaction->id;

            } else {
                InventoryTransaction::where('purchase_order_id', $request['purchase_order_id'])
                        ->where('product_id', $request['product_id'])
                        ->delete();
                
                $data['inventory_transaction_id'] =  null;
            }
        }

        return response()->json([
            'data' => $purchaseOrderDetail->update($data),
        ], 200);
    }

    public function destroy( PurchaseOrder $purchaseOrder, PurchaseOrderDetail $purchaseOrderDetail)
    { 
        return PurchaseOrderDetail::destroy($purchaseOrderDetail->id);
    }
}
