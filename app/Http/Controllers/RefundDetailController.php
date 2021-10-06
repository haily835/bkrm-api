<?php

namespace App\Http\Controllers;

use App\Models\RefundDetail;
use Illuminate\Http\Request;

class RefundDetailController extends Controller
{
    public function index(Request $request)
    {
        $store_id = $request['store_id'];
        $branch_id = $request['branch_id'];
        $refund_id = $request['refund_id'];
        return RefundDetail::where('store_id', $store_id)
                    ->where('branch_id', $branch_id)
                    ->where('refund_id', $refund_id)->get();
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
            'company' => 'required|string',
            'refund_id' => 'required|numeric',
            'product_id' => 'required|numeric',
            'inventory_transaction_id' => 'nullable|numeric',
            'quantity' => 'required|numeric',
            'unit_price' => 'required|numeric',
            'reason' => 'nullable|string',
            'store_id' => 'required|numeric',
            'branch_id' => 'required|numeric',
        ]);

        if (Store::where('id', $request['store_id'])->doesntExist()) {
            return response()->json(['message' => 'store_id do not exist']);
        }
        
        if (Branch::where('id', $request['branch_id'])->doesntExist()) {
            return response()->json(['message' => 'branch_id do not exist']);
        }

        if (Refund::where('id', $request['refund_id'])->doesntExist()) {
            return response()->json(['message' => 'refund_id do not exist']);
        }

        return RefundDetail::create($request->all());
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\RefundDetail  $refundDetail
     * @return \Illuminate\Http\Response
     */
    public function show(RefundDetail $refundDetail)
    {
        return $refundDetail;
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\RefundDetail  $refundDetail
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, RefundDetail $refundDetail)
    {
        $request->validate([
            'company' => 'required|string',
            'refund_id' => 'required|numeric',
            'product_id' => 'required|numeric',
            'inventory_transaction_id' => 'nullable|numeric',
            'quantity' => 'required|numeric',
            'unit_price' => 'required|numeric',
            'reason' => 'nullable|string',
            'store_id' => 'required|numeric',
            'branch_id' => 'required|numeric',
        ]);

        if (Store::where('id', $request['store_id'])->doesntExist()) {
            return response()->json(['message' => 'store_id do not exist']);
        }
        
        if (Branch::where('id', $request['branch_id'])->doesntExist()) {
            return response()->json(['message' => 'branch_id do not exist']);
        }

        if (Refund::where('id', $request['refund_id'])->doesntExist()) {
            return response()->json(['message' => 'refund_id do not exist']);
        }
        
        return $refundDetail->update($request->all());
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\RefundDetail  $refundDetail
     * @return \Illuminate\Http\Response
     */
    public function destroy(RefundDetail $refundDetail)
    {
        return RefundDetail::destroy($refundDetail->id);
    }
}
