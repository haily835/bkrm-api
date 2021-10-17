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

        return Order::where('store_id', $store_id)
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
        $validated = $request->validate([
            'branch_id' => 'required|numeric',
            'customer_id' => 'required|numeric',
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
            return response()->json(['message' => 'store_id do not exist'], 404);
        }
        
        if (Branch::where('id', $request['branch_id'])->doesntExist()) {
            return response()->json(['message' => 'branch_id do not exist'], 404);
        }


        return Order::create($validated);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Order  $order
     * @return \Illuminate\Http\Response
     */
    public function show(Order $order)
    {
        return $order;
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Order  $order
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Order $order)
    {
        return $order->update($request->all());
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Order  $order
     * @return \Illuminate\Http\Response
     */
    public function destroy(Order $order)
    {
        return Order::destroy($order->id);
    }


    public function addOrderDetail(Order $order) {
        $validated = $request->validate([
            'product_id' => 'required|numeric',
            'quantity' => 'required|numeric',
            'unit_price' => 'required|numeric',
            'discount' => 'required|numeric',
        ]);



    }
}
