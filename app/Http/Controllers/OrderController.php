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
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Employee;
use App\Models\BranchInventory;

class OrderController extends Controller
{
    public function index(Request $request, Store $store, Branch $branch)
    {
        // extract query string
        $start_date = $request->query('startDate');
        $end_date = $request->query('endDate');
        $min_total_amount = $request->query('minTotalAmount');
        $max_total_amount = $request->query ('maxTotalAmount');
        $min_discount = $request->query('minDiscount');
        $max_discount = $request->query('maxDiscount');
        $status = $request->query('status');
        $payment_method = $request->query('paymentMethod');
        $order_code = $request->query('orderCode');

        // set up query
        $queries = [];

        if($order_code) {
            array_push($queries, ['orders.order_code', 'LIKE', $order_code]);
        }


        if($start_date) {
            array_push($queries, ['orders.created_at', '>=', $start_date . ' 00:00:00']);
        }

        if($end_date) {
            array_push($queries, ['orders.created_at', '<=', $end_date . ' 00:00:00']);
        }

        if($min_total_amount) {
            array_push($queries, ['orders.total_amount', '>=', $min_total_amount]);
        }

        if($max_total_amount) {
            array_push($queries, ['orders.total_amount', '<=', $max_total_amount]);
        }

        if($min_discount) {
            array_push($queries, ['orders.discount', '>=', $min_total_amount]);
        }

        if($max_discount) {
            array_push($queries, ['orders.discount', '<=', $max_discount]);
        }

        if($status) {
            array_push($queries, ['orders.status', '>=', $min_total_amount]);
        }

        if($payment_method) {
            array_push($queries, ['payment_method', '<=', $payment_method]);
        }

        $orders = $branch->orders()
            ->where($queries)
            ->join('customers', 'orders.customer_id', '=', 'customers.id')
            ->join('branches', 'orders.branch_id', '=', 'branches.id')
            ->select('orders.*', 'customers.name as customer_name', 'branches.name as branch_name')->get();
        
        
        return response()->json([
            'data' => $orders,
        ], 200);
        
    }

    public function getStoreOrder(Store $store) {
        $data = $store->orders()
            ->join('customers', 'orders.customer_id', '=', 'customers.id')
            ->join('branches', 'orders.branch_id', '=', 'branches.id')
            ->select('orders.*', 'customers.name as customer_name', 'branches.name as branch_name')->get();
                                    
        return response()->json([
            'data' => $data,
        ]);
    }


    public function addOrder(Request $request, Store $store, Branch $branch) {
        $validated = $request->validate([
            'customer_uuid' => 'required|string',
            'paid_date' => 'required|date_format:Y-m-d H:i:s',
            'creation_date' => 'required|date_format:Y-m-d H:i:s',
            'total_amount' => 'required|string',
            'paid_amount' => 'required|string',
            'discount' => 'required|string',
            'payment_method' => 'required|string',
            'notes' => 'nullable|string',
            'status' => 'nullable|string|in:new,invoiced,shipped,closed,debt,',
            'details' => 'required',
            'tax' => 'required|string',
            'shipping' => 'required|string',
        ]);

        # get the user send request by token
        $created_by = null;
        $created_user_type = '';
        if (Auth::guard('user')->user()) {
            $created_by = Auth::guard('user')->user()->id;
            $created_user_type = 'owner';
        } else if (Auth::guard('employee')->user()){
            $created_by = Auth::guard('employee')->user()->id;
            $created_user_type = 'employee';
        } else {
            return response()->json([
                'message' => 'Unauthorized',
            ], 401);
        }

        $customer_id = $store->customers()->where('uuid',$validated['customer_uuid'])->first()->id;

        # generate code
        $last_id = $store->orders()->count();

        $orderCode = 'DH' . sprintf( '%06d', $last_id );

        $order = Order::create([
            'store_id' => $store->id,
            'uuid' => (string) Str::uuid(),
            'branch_id' => $branch->id,
            'customer_id' => $customer_id,
            'user_id' => $created_by,
            'payment_method' => $validated['payment_method'],
            'paid_date' => $validated['paid_date'],
            'paid_amount' => $validated['paid_amount'],
            'total_amount' => $validated['total_amount'],
            'creation_date' => $validated['creation_date'],
            'discount' => $validated['discount'],
            'created_user_type' => $created_user_type,
            'status' => $validated['status'],
            'notes' => '',
            'order_code' => $orderCode
        ]);


        

        foreach ($validated['details'] as $detail) {
            $product_id = $store->products->where('uuid', '=', $detail['uuid'])->first()->id;
            $inventoryTransaction = InventoryTransaction::create([
                'uuid' => (string)Str::uuid(),
                'store_id' => $store->id,
                'branch_id' => $branch->id,
                'product_id' => $product_id,
                'quantity' => $detail['quantity'],
                'transaction_type' => 'sold',
                // 'document_type' => 2,
                // 'document_id' => $order->id,
            ]);

            OrderDetail::create([
                'store_id' => $store->id,
                'branch_id' => $branch->id,
                'order_id' => $order->id,
                'product_id' => $product_id,
                'unit_price' => $detail['unit_price'],
                'inventory_transaction_id' => $inventoryTransaction->id,
                'discount' => $detail['discount'],
                'quantity' => $detail['quantity'],
                'status' => 'shipped',
                'discount' => $detail['discount']
            ]);

            $product = $store->products->where('uuid', '=', $detail['uuid'])->first();
            $newQuantity = (string)((int) $product->quantity_available) - ((int) $detail['quantity']);
            $product->update(['quantity_available' => $newQuantity]);


            // update branch inventory table
            $productOfStore = BranchInventory::where([['branch_id', '=', $branch->id], ['product_id', '=', $product_id]])->first();

            if ($productOfStore) {
                BranchInventory::where([['branch_id', '=', $branch->id], ['product_id', '=', $product_id]])
                    ->decrement('quantity_available', $detail['quantity']);
            } else {
                BranchInventory::create([
                    'store_id' => $store->id,
                    'branch_id' => $branch->id,
                    'product_id' => $product_id,
                    'quantity_available' => $detail['quantity'],
                ]);
            }
        }

        # generate code
        $last_id = $store->invoices()->count();
        $invoiceCode = 'HD' . sprintf( '%06d', $last_id );

        $invoice = Invoice::create([
            'uuid' => (string) Str::uuid(),
            'order_id' => $order->id,
            'branch_id' => $branch->id,
            'due_date' => $validated['paid_date'],
            'tax' => $validated['tax'],
            'shipping' => $validated['shipping'],
            'amount_due' => $validated['paid_amount'],
            'invoice_code' => $invoiceCode,
            'store_id' => $store->id,
        ]);

        return response()->json([
            'message' => 'Order created successfully',
            'data' => [
                'order' => $order,
                'invoice' => $invoice,
            ]
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
            'status' => 'nullable|string|in:new,invoiced,shipped,closed,debt',
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

    public function show(Store $store, Order $order)
    {
        $details = $order->orderDetails()
        ->join('products', 'order_details.product_id', '=', 'products.id')
        ->select('order_details.*', 'products.name', 'products.bar_code')->get();

        if ($order->created_user_type === 'owner') {
            $created_by = User::where('id', $order->user_id)->first();
        } else {
            $created_by = Employee::where('id', $order->user_id)->first();
        }
        $data = array_merge([
            'customer' => $order->customer,
            'branch' => $order->branch,
            'details' => $details,
            'created_by_user' => $created_by,
        ], $order->toArray());

        return response()->json([
            'data' => $data
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
