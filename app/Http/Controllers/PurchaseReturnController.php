<?php

namespace App\Http\Controllers;

use App\Models\PurchaseReturn;
use App\Models\Store;
use App\Models\Branch;
use Illuminate\Http\Request;

class PurchaseReturnController extends Controller
{
    public function index(Store $store, Branch $branch)
    {
        return response()->json([
            'data' => $branch->purchaseReturns,
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
