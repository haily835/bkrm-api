<?php

namespace App\Http\Controllers;

use App\Models\Refund;
use App\Models\Store;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Branch;
use Illuminate\Http\Request;

class RefundController extends Controller
{
    public function index(Store $store, Branch $branch)
    {
        return response()->json([
            'data' => $branch->refunds,
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, Store $store, Branch $branch)
    {
        $validated = $request->validate([
            'user_id' => 'required|numeric',
            'invoice_id' => 'required|numeric',
            'payment_type' => 'required|string',
            'notes' => 'nullable|string',
        ]);

        $invoice = Invoice::find($validated['invoice_id'])->first();

        $order = Order::find($invoice->order_id)->first();

        
        $refund = Refund::create(array_merge(
            [
                'customer_id' => $order->customer_id,
                'order_id' => $order->id,
                'store_id' => $store->id,
                'branch_id' => $branch->id,
            ],
            $validated
        ));


        return response()->json([
            'message' => 'Refund created',
            'data' => $refund,
        ], 200);
    }

    public function show(Store $store, Branch $branch, Refund $refund)
    {
        return response()->json([
            'data' => $refund,
        ], 200);
    }

    public function update(Request $request, Store $store, Branch $branch, Refund $refund)
    {
        $validated = $request->validate([
            'payment_type' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $refund->update($validated);

        return response()->json([
            'message' => 'Refund updated',
            'data' => $refund,
        ], 200);
    }
    
    public function destroy(Store $store, Branch $branch, Refund $refund)
    {
        $isdeleted = Refund::destroy($refund->id);
        return response()->json([
            'message' => $isdeleted,
            'data' => $refund
        ], 200);
    }
}
