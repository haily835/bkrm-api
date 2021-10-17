<?php

namespace App\Http\Controllers;

use App\Models\PurchaseOrder;
use App\Models\Store;
use App\Models\Branch;
use Illuminate\Http\Request;

class PurchaseOrderController extends Controller
{

    public function index(Request $request)
    {
        $store_id = $request->query('store_id');
        $branch_id = $request->query('branch_id');

        if (Store::where('id', $store_id)->doesntExist()) {
            return response()->json(['message' => 'store_id do not exist'], 404);
        }

        if (Branch::where('id', $branch_id)->doesntExist()) {
            return response()->json(['message' => 'branch_id do not exist'], 404);
        }
        return PurchaseOrder::where('store_id', $store_id)
                            ->where('branch_id', $branch_id)->get();
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
            'supplier_id' => 'required|numeric',
            'created_by' => 'required|numeric',
            'approved_by' => 'required|numeric',
            'store_id' => 'required|numeric',
            'creation_date' => 'required|date_format:Y-m-d',
            'approved_date' => 'nullable|date_format:Y-m-d',
            'payment_date' => 'nullable|date_format:Y-m-d',
            'payment_amount' => 'required|numeric',
            'payment_method' => 'string|nullable',
            'notes' => 'string|nullable',
            'status' => 'string|required',
            'branch_id' => 'required|numeric'
        ]);

        if (Store::where('id', $request['store_id'])->doesntExist()) {
            return response()->json(['message' => 'store_id do not exist']);
        }
        
        if (Branch::where('id', $request['branch_id'])->doesntExist()) {
            return response()->json(['message' => 'branch_id do not exist']);
        }

        return PurchaseOrder::create($request->all());
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\PurchaseOrder  $purchaseOrder
     * @return \Illuminate\Http\Response
     */
    public function show(PurchaseOrder $purchaseOrder)
    {
        return $purchaseOrder;
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\PurchaseOrder  $purchaseOrder
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, PurchaseOrder $purchaseOrder)
    {
        $request->validate([
            'supplier_id' => 'required|numeric',
            'branch_id' => 'required|numeric',
            'store_id' => 'required|numeric',
            'created_by' => 'required|numeric',
            'creation_date' => 'nullable|date_format:Y-m-d',
            'approved_by' => 'nullable|numeric',
            'approved_date' => 'nullable|date_format:Y-m-d',
            'payment_date' => 'nullable|date_format:Y-m-d',
            'payment_amount' => 'nullable|numeric',
            'payment_method' => 'nullable|string',
            'notes' => 'nullable|string',
            'status' => 'nullable|string|in:new,submitted,approved,closed',
        ]);

        if (Store::where('id', $request['store_id'])->doesntExist()) {
            return response()->json(['message' => 'store_id do not exist']);
        }
        
        if (Branch::where('id', $request['branch_id'])->doesntExist()) {
            return response()->json(['message' => 'branch_id do not exist']);
        }
        return $purchaseOrder->update($request->all());
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\PurchaseOrder  $purchaseOrder
     * @return \Illuminate\Http\Response
     */
    public function destroy(PurchaseOrder $purchaseOrder)
    {
        return PurchaseOrder::destroy($purchaseOrder->id);
    }
}
