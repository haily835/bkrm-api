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
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $store_id = $request->query('store_id');
        $branch_id = $request->query('branch_id');
        $purchase_order_id = $request->query('purchase_order_id');

        if (Store::where('id', $store_id)->doesntExist()) {
            return response()->json(['message' => 'store_id do not exist'], 404);
        }

        if (Branch::where('id', $branch_id)->doesntExist()) {
            return response()->json(['message' => 'branch_id do not exist'], 404);
        }

        if (PurchaseOrder::where('id', $purchase_order_id)->doesntExist()) {
            return response()->json(['message' => 'purchase_order_id do not exist'], 404);
        }
       
        
        return PurchaseOrderDetail::where('store_id', $store_id)
                                ->where('branch_id', $branch_id)
                                ->where('purchase_order_id', $purchase_order_id)->get();
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
            'purchase_order_id' => 'required|numeric',
            'product_id' => 'required|numeric',
            'store_id' => 'required|numeric',
            'branch_id' => 'required|numeric',
            'quantity' => 'required|numeric',
            'unit_cost' => 'required|numeric',
            'date_received' => 'required|date_format:Y-m-d',
            'posted_to_inventory' => 'required|boolean',
        ]);


        if (Store::where('id', $request['store_id'])->doesntExist()) {
            return response()->json(['message' => 'store_id do not exist']);
        }
        
        if (Branch::where('id', $request['branch_id'])->doesntExist()) {
            return response()->json(['message' => 'branch_id do not exist']);
        }

        if (PurchaseOrder::where('id', $request['refund_id'])->doesntExist()) {
            return response()->json(['message' => 'refund_id do not exist']);
        }

        return PurchaseOrderDetail::create($request->all());
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\PurchaseOrderDetail  $purchaseOrderDetail
     * @return \Illuminate\Http\Response
     */
    public function show(PurchaseOrderDetail $purchaseOrderDetail)
    {
        return $purchaseOrderDetail;
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\PurchaseOrderDetail  $purchaseOrderDetail
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, PurchaseOrderDetail $purchaseOrderDetail)
    {
        $data = $request->validate([
            'purchase_order_id' => 'required|numeric',
            'product_id' => 'required|numeric',
            'store_id' => 'required|numeric',
            'branch_id' => 'required|numeric',
            'quantity' => 'nullable|numeric',
            'unit_cost' => 'nullable|numeric',
            'date_received' => 'nullable|date_format:Y-m-d',
            'posted_to_inventory' => 'nullable|boolean',
        ]);

        if (Store::where('id', $request['store_id'])->doesntExist()) {
            return response()->json(['message' => 'store_id do not exist']);
        }
        
        if (Branch::where('id', $request['branch_id'])->doesntExist()) {
            return response()->json(['message' => 'branch_id do not exist']);
        }

        if (PurchaseOrder::where('id', $request['refund_id'])->doesntExist()) {
            return response()->json(['message' => 'refund_id do not exist']);
        }


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
        return $purchaseOrderDetail->update($data);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\PurchaseOrderDetail  $purchaseOrderDetail
     * @return \Illuminate\Http\Response
     */
    public function destroy(PurchaseOrderDetail $purchaseOrderDetail)
    {
        return PurchaseOrderDetail::destroy($purchaseOrderDetail->id);
    }
}
