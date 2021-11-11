<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Store;
use App\Models\Branch;
use App\Models\OrderDetail;
use App\Models\InventoryTransaction;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Support\Str;


class OrderController extends Controller
{
    public function index(Store $store, Branch $branch)
    {
        return response()->json([
            'data' => $branch->orders,
        ], 200);
    }

    public function checkOut(Request $request, Store $store, Branch $branch) {
        $validated = $request->validate([
            'customer_uuid' => 'required|numeric',
            'paid_date' => 'nullable|date_format:Y-m-d',
            'payment_type' => 'nullable|date_format:Y-m-d',
            'payment_amount' => 'nullable|numeric',
            'payment_method' => 'nullable|string',
            'notes' => 'nullable|string',
            'status' => 'nullable|string|in:new,invoiced,shipped,closed',
            'details' => 'required',
            'tax' => 'required|numeric',
            'shipping' => 'required|numeric',
            'amount_due' => 'required|numeric'
        ]);

        $order = Order::create([
            'user_id' => auth()->user()->id,
            'store_id' => $store->id,
            'branch_id' => $branch->id,
            'payment_type' => $validated['payment_type'],
            'paid_date' => $validated['paid_date'],
            'status' => $validated['status'],
            'notes' => $validated['notes'],
        ]);

        foreach ($validated['details'] as $detail) {
            $inventoryTransaction = InventoryTransaction::create([
                'uuid' => (string)Str::uuid(),
                'store_id' => $store->id,
                'product_id' => $detail['product_id'],
                'purchase_order_id' => $order->id,
                'quantity' => $detail['quantity'],
                'branch_id' => $branch->id,
                'transaction_type' => 'sold',
            ]);

            OrderDetail::create(
                [
                    'store_id' => $store->id,
                    'branch_id' => $branch->id,
                    'inventory_transaction_id' => $inventoryTransaction->id,
                    'order_id' => $order->id,
                    'date_received' => date("Y-m-d"),
                    'unit_price' => $detail['unit_price'],
                    'quantity' => $detail['quantity'],
                    'status' => 'shipped',
                    'discount' => $detail['discount']
                ]
            );
        }

        Invoice::create([
            'uuid' => (string) Str::uuid(),
            'order_id' => $order->id,
            'branch_id' => $branch->id,
            'due_date' => $validated['paid_date'],
            'tax' => $validated['tax'],
            'shipping' => $validated['shipping'],
            'amount_due' => $validated['amount_due']
        ]);

        return response()->json([
            'message' => 'Order created successfully',
            'data' => $order,
        ], 200);

    }

    public function store(Request $request, Store $store, Branch $branch)
    {
        $validated = $request->validate([
            'customer_uuid' => 'required|numeric',
            'paid_date' => 'nullable|date_format:Y-m-d',
            'payment_type' => 'nullable|date_format:Y-m-d',
            'payment_amount' => 'nullable|numeric',
            'payment_method' => 'nullable|string',
            'notes' => 'nullable|string',
            'status' => 'nullable|string|in:new,invoiced,shipped,closed',
        ]);

        $order = array_merge($validated, [
            'user_id' => auth()->user()->id,
            'store_id' => $store->id,
            'branch_id' => $branch->id
        ]);

        $newOrder = Order::create($order);

        return response()->json([
            'message' => 'Order created successfully',
            'data' => $newOrder,
        ], 200);
    }

    public function show(Store $store, Branch $branch, Order $order)
    {
        return response()->json([
            'data' => $order,
        ], 200);
    }


    public function update(Request $request, Store $store, Branch $branch, Order $order)
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

        $order->update($validated);

        if ($validated['status'] === 'closed') {
        }

        return response()->json([
            'message' => 'Order updated successfully',
            'data' => $order,
        ], 200);
    }

    public function destroy(Store $store, Branch $branch, Order $order)
    {
        $isDeleted = Order::destroy($order->id);
        return response()->json([
            'message' => $isDeleted,
            'data' => $order,
        ], 200);
    }
}
