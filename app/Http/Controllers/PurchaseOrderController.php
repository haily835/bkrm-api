<?php

namespace App\Http\Controllers;

use App\Models\PurchaseOrder;
use App\Models\Store;
use App\Models\Branch;
use Illuminate\Http\Request;

class PurchaseOrderController extends Controller
{

    public function index(Store $store, Branch $branch)
    {
        return response()->json([
            'data' => $branch->purchaseOrders,
        ], 200);
    }

    public function store(Request $request, Store $store, Branch $branch)
    {
        $validated = $request->validate([
            'supplier_id' => 'required|numeric',
            'created_by' => 'required|numeric',
            'approved_by' => 'required|numeric',
            'creation_date' => 'required|date_format:Y-m-d',
            'approved_date' => 'nullable|date_format:Y-m-d',
            'payment_date' => 'nullable|date_format:Y-m-d',
            'payment_amount' => 'required|numeric',
            'payment_method' => 'nullable|string',
            'notes' => 'nullable|string',
            'status' => 'required|string',
        ]);

        $purchaseOrder = PurchaseOrder::create($validated, [
            'store_id' => $store->id,
            'branch_id' => $branch->id
        ]);

        return response()->json([
            'message' => 'Purchase order created successfully',
            'data' => $purchaseOrder,
        ], 200);
    }


    public function update(Request $request, PurchaseOrder $purchaseOrder)
    {
        $validated = $request->validate([
            'approved_by' => 'nullable|numeric',
            'approved_date' => 'nullable|date_format:Y-m-d',
            'payment_date' => 'nullable|date_format:Y-m-d',
            'payment_amount' => 'required|numeric',
            'payment_method' => 'nullable|string',
            'notes' => 'nullable|string',
            'status' => 'nullable|string',
        ]);

        $purchaseOrder->update($validated);

        return response()->json([
            'message'=> 'Purchase order updated successfully',
            'data' => $purchaseOrder,
        ], 200);
    }

    public function destroy(PurchaseOrder $purchaseOrder)
    {
        return PurchaseOrder::destroy($purchaseOrder->id);
    }
}
