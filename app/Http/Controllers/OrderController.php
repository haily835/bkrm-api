<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Store;
use App\Models\Branch;
use App\Models\OrderDetail;
use App\Models\InventoryTransaction;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index(Store $store, Branch $branch)
    {
        return response()->json([
            'data' => $branch->orders,
        ], 200);
    }

    public function store(Request $request, Store $store, Branch $branch)
    {
        $validated = $request->validate([
            'user_id' => ' required|numeric',
            'customer_id' => 'required|numeric',
            'paid_date' => 'nullable|date_format:Y-m-d',
            'payment_type' => 'nullable|date_format:Y-m-d',
            'payment_amount' => 'nullable|numeric',
            'payment_method' => 'nullable|string', 
            'notes' => 'nullable|string',
            'status' => 'nullable|string|in:new,invoiced,shipped,closed',
        ]);

        $order = array_merge($validated, [
            'store_id' => $store->id,
            'branch_id' => $branch->id
        ]);

        Order::create($order);

        return response()->json([
            'message' => 'Order created successfully',
            'data' => $order,
        ], 200);
    }

    
    public function update(Request $request, Order $order)
    {
        $validated = $request->validate([
            'customer_id' => 'required|numeric',
            'paid_date' => 'nullable|date_format:Y-m-d',
            'payment_type' => 'nullable|date_format:Y-m-d',
            'payment_amount' => 'nullable|numeric',
            'payment_method' => 'nullable|string', 
            'notes' => 'nullable|string',
            'status' => 'nullable|string|in:new,invoiced,shipped,closed',
        ]);

        return response()->json([
            'message'=> 'Order updated successfully',
            'data' => $order->update($validated),
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Order  $order
     * @return \Illuminate\Http\Response
     */
    public function destroy(Store $store, Order $order)
    {
        return Order::destroy($order->id);
    }
}
