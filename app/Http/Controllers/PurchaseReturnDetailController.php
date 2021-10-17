<?php

namespace App\Http\Controllers;

use App\Models\PurchaseReturnDetail;
use App\Models\PurchaseReturn;
use App\Models\Store;
use App\Models\Branch;
use Illuminate\Http\Request;

class PurchaseReturnDetailController extends Controller
{

    public function index(Request $request)
    {
        $store_id = $request->query('store_id');
        $branch_id = $request->query('branch_id');
        $purchase_return_id = $request->query('purchase_return_id');

        if (Store::where('id', $store_id)->doesntExist()) {
            return response()->json(['message' => 'store_id do not exist'], 404);
        }

        if (Branch::where('id', $branch_id)->doesntExist()) {
            return response()->json(['message' => 'branch_id do not exist'], 404);
        }

        if (PurchaseReturn::where('id', $purchase_return_id)->doesntExist()) {
            return response()->json(['message' => 'purchase_return_id do not exist'], 404);
        }
       
        return PurchaseReturnDetail::where('store_id', $store_id)
                    ->where('branch_id', $branch_id)
                    ->where('purchase_return_id', $purchase_return_id)->get();
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'purchase_return_id' => 'required|numeric',
            'product_id' => 'required|numeric',
            'store_id' => 'required|numeric',
            'branch_id' => 'required|numeric',
            'inventory_transaction_id' => 'nullable|numeric',
            'quantity' => 'required|numeric',
            'unit_cost' => 'required|numeric',
            'reason' => 'nullable|string',
        ]);

        if (Store::where('id', $request['store_id'])->doesntExist()) {
            return response()->json(['message' => 'store_id do not exist']);
        }
        
        if (Branch::where('id', $request['branch_id'])->doesntExist()) {
            return response()->json(['message' => 'branch_id do not exist']);
        }

        if (PurchaseReturn::where('id', $request['purchase_return_id'])->doesntExist()) {
            return response()->json(['message' => 'purchase_return_id do not exist']);
        }

        return PurchaseReturnDetail::create($request->all());
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\PurchaseReturnDetail  $purchaseReturnDetail
     * @return \Illuminate\Http\Response
     */
    public function show(PurchaseReturnDetail $purchaseReturnDetail)
    {
        return $purchaseReturnDetail;
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\PurchaseReturnDetail  $purchaseReturnDetail
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, PurchaseReturnDetail $purchaseReturnDetail)
    {
        $request->validate([
            'purchase_return_id' => 'required|numeric',
            'product_id' => 'required|numeric',
            'store_id' => 'required|numeric',
            'branch_id' => 'required|numeric',
            'inventory_transaction_id' => 'nullable|numeric',
            'quantity' => 'nullable|numeric',
            'unit_cost' => 'nullable|numeric',
            'reason' => 'nullable|string',
        ]);

        // changing the inventory transaction 


        return $purchaseReturnDetail->update($request->all());
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\PurchaseReturnDetail  $purchaseReturnDetail
     * @return \Illuminate\Http\Response
     */
    public function destroy(PurchaseReturnDetail $purchaseReturnDetail)
    {
        return PurchaseReturnDetail::destroy($purchaseReturnDetail->id);
    }
}
