<?php

namespace App\Http\Controllers;

use App\Models\Refund;
use App\Models\Store;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Branch;
use Illuminate\Http\Request;
use App\Models\BranchInventory;
use App\Models\InventoryTransaction;
use App\Models\RefundDetail;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Employee;
use Illuminate\Support\Str;


class RefundController extends Controller
{
    public function getStoreRefund(Store $store) {
        $data = $store->refunds()
            ->join('customers', 'refunds.customer_id', '=', 'customers.id')
            ->join('branches', 'refunds.branch_id', '=', 'branches.id')
            ->select('refunds.*', 'customers.name as customer_name', 'branches.name as branch_name')->get();
                                    
        return response()->json([
            'data' => $data,
        ]);
    }

    public function removeInventory(Request $request, Store $store, Branch $branch) {
        $validated = $request->validate([
            'order_uuid' => 'required|string',
            'customer_id' => 'required|numeric',
            'paid_amount' => 'required|string',
            'payment_method' => 'required|string',
            'total_amount' => 'required|string',
            'status' => 'required|string',
            'details' => 'required',
            'import_date' => 'required|date_format:Y-m-d H:i:s',
        ]);

        // get the user of  token
        $created_by = $approved_by = null;
        $created_user_type = '';

        if (Auth::guard('user')->user()) {
            $created_by = $approved_by = Auth::guard('user')->user()->id;
            $created_user_type = 'owner';
        } else if (Auth::guard('employee')->user()){
            $created_by = $approved_by = Auth::guard('employee')->user()->id;
            $created_user_type = 'employee';
        } else {
            return response()->json([
                'message' => 'Unauthorized',
            ], 401);
        }

        $creation_date = $approved_date = $validated['import_date'];

        $order_id = $store->orders()->where('uuid',$validated['order_uuid'])->first()->id;

        $last_id = $store->refunds()->count();

        $refundCode = 'TH' . sprintf( '%06d', $last_id );

        $refund = Refund::create([
            'store_id' => $store->id,
            'uuid' => (string)Str::uuid(),
            'branch_id' => $branch->id,
            'customer_id' => $validated['customer_id'],
            'refund_code' => $refundCode,
            'created_by' => $created_by,
            'paid_amount' => $validated['paid_amount'],
            'payment_method' => $validated['payment_method'],
            'total_amount' => $validated['total_amount'],
            'created_user_type' => $created_user_type,
            'order_id' => $order_id,
            'status' => $validated['status'],
        ]);

        foreach ($validated['details'] as $detail) {
            $inventoryTransaction = InventoryTransaction::create([
                'uuid' => (string)Str::uuid(),
                'store_id' => $store->id,
                'product_id' => $detail['product_id'],
                'quantity' => $detail['quantity'],
                'branch_id' => $branch->id,
                'transaction_type' => 'supplier_returned',
            ]);

            RefundDetail::create([
                'refund_id' => $refund->id,
                'product_id' => $detail['product_id'],
                'inventory_transaction_id' => $inventoryTransaction->id,
                'quantity' => $detail['quantity'],
                'unit_price' => $detail['unit_price'],
                'store_id' => $store->id,
                'branch_id' => $branch->id,
                'posted_to_inventory' => true,
            ]);

            $product = $store->products->where('id', '=', $detail['product_id'])->first();
            $newQuantity = (string)((int) $product->quantity_available) + ((int) $detail['quantity']);
            $product->update(['quantity_available' => $newQuantity]);
            // $productOfStore = BranchInventory::where([['branch_id', '=', $branch->id], ['product_id', '=', $product_id]])->first();

            // if ($productOfStore) {
            //     $newQuantity = ((float) $productOfStore->quantity_available) + ((float) $detail['quantity']);
            //     $productOfStore->update(['quantity', $newQuantity]);
            // } else {
            //     BranchInventory::create([
            //         'store_id' => $store->id,
            //         'branch_id' => $branch->id,
            //         'product_id' => $product_id,
            //         'quantity_available' => $detail['quantity'],
            //     ]);
            // }
        }

        return response()->json([
            'message' => 'Refund created successfully',
            'data' => $refund,
        ], 200);
    }

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
