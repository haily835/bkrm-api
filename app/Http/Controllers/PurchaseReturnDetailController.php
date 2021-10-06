<?php

namespace App\Http\Controllers;

use App\Models\PurchaseReturnDetail;
use Illuminate\Http\Request;

class PurchaseReturnDetailController extends Controller
{

    public function index(Request $request)
    {
        $store_id = $request['store_id'];
        $branch_id = $request['branch_id'];
        $purchase_return_id = $request['purchase_return_id'];
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
