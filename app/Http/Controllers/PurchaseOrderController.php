<?php

namespace App\Http\Controllers;

use App\Models\PurchaseOrder;
use App\Models\Store;
use App\Models\Branch;
use App\Models\InventoryTransaction;
use App\Models\PurchaseOrderDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PurchaseOrderController extends Controller
{
    public function index(Store $store, Branch $branch)
    {
        return response()->json([
            'data' => $branch->purchaseOrders,
        ], 200);
    }

    public function addInventory(Request $request, Store $store, Branch $branch)
    {
        $validated = $request->validate([
            'supplier_id' => 'required|numeric',
            'created_by' => 'required|numeric',
            'approved_by' => 'nullable|numeric',
            'creation_date' => 'required|date_format:Y-m-d',
            'approved_date' => 'nullable|date_format:Y-m-d',
            'payment_date' => 'nullable|date_format:Y-m-d',
            'payment_amount' => 'required|numeric',
            'payment_method' => 'nullable|string',
            'notes' => 'nullable|string',
            'taxes' => 'required|numeric',
            'status' => 'required|string',
            'details' => 'required'
        ]);

        $purchaseOrderData = $validated;
        unset($purchaseOrderData['details']);
 
        $purchaseOrder = PurchaseOrder::create(array_merge([
            'store_id' => $store->id,
            'uuid' => (string)Str::uuid(),
            'branch_id' => $branch->id,
        ], $purchaseOrderData));

        foreach ($validated['details'] as $detail) {
            $inventoryTransaction = InventoryTransaction::create([
                'uuid' => (string)Str::uuid(),
                'store_id' => $store->id,
                'product_id' => $detail['product_id'],
                'purchase_order_id' => $purchaseOrder->id,
                'quantity' => $detail['quantity'],
                'branch_id' => $branch->id,
                'transaction_type' => 'purchased',
            ]);

            PurchaseOrderDetail::create(
                [
                    'store_id' => $store->id,
                    'branch_id' => $branch->id,
                    'inventory_transaction_id' => $inventoryTransaction->id,
                    'purchase_order_id' => $purchaseOrder->id,
                    'posted_to_inventory' => true,
                    'date_received' => date("Y-m-d"),
                    'unit_cost' => $detail['unit_cost'],
                    'quantity' => $detail['quantity']
                ]
            );
        }

        return response()->json([
            'message' => 'Purchase order created successfully',
            'data' => $purchaseOrder,
        ], 200);
    }

    public function store(Request $request, Store $store, Branch $branch)
    {
        $validated = $request->validate([
            'supplier_id' => 'required|numeric',
            'created_by' => 'required|numeric',
            'approved_by' => 'nullable|numeric',
            'creation_date' => 'required|date_format:Y-m-d',
            'approved_date' => 'nullable|date_format:Y-m-d',
            'payment_date' => 'nullable|date_format:Y-m-d',
            'payment_amount' => 'required|numeric',
            'payment_method' => 'nullable|string',
            'notes' => 'nullable|string',
            'taxes' => 'required|numeric',
            'status' => 'required|string'
        ]);

        $purchaseOrder = PurchaseOrder::create(array_merge($validated, [
            'store_id' => $store->id,
            'branch_id' => $branch->id
        ]));

        return response()->json([
            'message' => 'Purchase order created successfully',
            'data' => $purchaseOrder,
        ], 200);
    }


    public function update(Request $request, Store $store, Branch $branch, PurchaseOrder $purchaseOrder)
    {
        $validated = $request->validate([
            'approved_by' => 'nullable|numeric',
            'approved_date' => 'nullable|date_format:Y-m-d',
            'payment_date' => 'nullable|date_format:Y-m-d',
            'payment_amount' => 'nullable|numeric',
            'payment_method' => 'nullable|string',
            'notes' => 'nullable|string',
            'status' => 'nullable|string',
        ]);

        $purchaseOrder->update($validated);

        return response()->json([
            'message' => 'Purchase order updated successfully',
            'data' => $purchaseOrder,
        ], 200);
    }

    public function show(Store $store, Branch $branch, PurchaseOrder $purchaseOrder)
    {
        return response()->json([
            'data' => $purchaseOrder,
        ], 200);
    }
    public function destroy(Store $store, Branch $branch, PurchaseOrder $purchaseOrder)
    {
        $isdelected = PurchaseOrder::destroy($purchaseOrder->id);
        return response()->json([
            'message' => $isdelected,
            'data' => $purchaseOrder,
        ], 200);
    }
}
