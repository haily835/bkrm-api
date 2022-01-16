<?php

namespace App\Http\Controllers;

use App\Models\PurchaseOrder;
use App\Models\Store;
use App\Models\Branch;
use App\Models\BranchInventory;
use App\Models\InventoryTransaction;
use App\Models\PurchaseOrderDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Employee;

class PurchaseOrderController extends Controller
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
        $purchase_order_code = $request->query('purchase_order_code');

        // set up query
        $queries = [];

        if($purchase_order_code) {
            array_push($queries, ['purchase_orders.purchase_order_code', 'LIKE', $purchase_order_code]);
        }

        if($start_date) {
            array_push($queries, ['purchase_orders.creation_date', '>=', $start_date]);
        }

        if($end_date) {
            array_push($queries, ['purchase_orders.creation_date', '<=', $end_date]);
        }

        if($min_total_amount) {
            array_push($queries, ['purchase_orders.total_amount', '>=', $min_total_amount]);
        }

        if($max_total_amount) {
            array_push($queries, ['purchase_orders.total_amount', '<=', $max_total_amount]);
        }

        if($min_discount) {
            array_push($queries, ['purchase_orders.discount', '>=', $min_total_amount]);
        }

        if($max_discount) {
            array_push($queries, ['purchase_orders.discount', '<=', $max_discount]);
        }

        if($status) {
            array_push($queries, ['purchase_orders.status', '>=', $min_total_amount]);
        }

        if($payment_method) {
            array_push($queries, ['purchase_orders.payment_method', '<=', $payment_method]);
        }

        $purchaseOrders = $branch->purchaseOrders()
            ->where($queries)
            ->join('suppliers', 'purchase_orders.supplier_id', '=', 'suppliers.id')
            ->join('branches', 'purchase_orders.branch_id', '=', 'branches.id')
            ->select('purchase_orders.*', 'suppliers.name as supplier_name', 'branches.name as branch_name')->get();

        return response()->json([
            'data' => $purchaseOrders,
        ], 200);
    }

    public function getStorePurchaseOrder(Store $store) {
        $data = $store->purchaseOrders()
            ->join('suppliers', 'purchase_orders.supplier_id', '=', 'suppliers.id')
            ->join('branches', 'purchase_orders.branch_id', '=', 'branches.id')
            ->select('purchase_orders.*', 'suppliers.name as supplier_name', 'branches.name as branch_name')->get();
                                    
        return response()->json([
            'data' => $data,
        ]);
    }

    public function addInventory(Request $request, Store $store, Branch $branch)
    {
        $validated = $request->validate([
            'supplier_uuid' => 'required|string',
            'paid_amount' => 'required|string',
            'payment_method' => 'required|string',
            'total_amount' => 'required|string',
            'discount' => 'required|string',
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

        $supplier_id = $store->suppliers()->where('uuid',$validated['supplier_uuid'])->first()->id;

        $last_id = $store->purchaseOrders()->count();

        $purchaseOrderCode = 'PO' . sprintf( '%06d', $last_id );

        $purchaseOrder = PurchaseOrder::create([
            'store_id' => $store->id,
            'uuid' => (string)Str::uuid(),
            'branch_id' => $branch->id,
            'supplier_id' => $supplier_id,
            'purchase_order_code' => $purchaseOrderCode,
            'approved_by' => $approved_by,
            'created_by' => $created_by,
            'creation_date' => $creation_date,
            'approved_date' => $approved_date,
            'payment_date' => $creation_date,
            'paid_amount' => $validated['paid_amount'],
            'payment_method' => $validated['payment_method'],
            'total_amount' => $validated['total_amount'],
            'discount' => $validated['discount'],
            'taxes' => 0,
            'created_user_type' => $created_user_type,
            'status' => $validated['status'],
        ]);

        foreach ($validated['details'] as $detail) {
            $product_id = $store->products->where('uuid', '=', $detail['uuid'])->first()->id;
            
            $inventoryTransaction = InventoryTransaction::create([
                'uuid' => (string)Str::uuid(),
                'store_id' => $store->id,
                'product_id' => $product_id,
                'quantity' => $detail['quantity'],
                'branch_id' => $branch->id,
                'transaction_type' => 'purchased',
            ]);

            PurchaseOrderDetail::create([
                'store_id' => $store->id,
                'branch_id' => $branch->id,
                'product_id' => $product_id,
                'inventory_transaction_id' => $inventoryTransaction->id,
                'purchase_order_id' => $purchaseOrder->id,
                'posted_to_inventory' => true,
                'date_received' => $validated['import_date'],
                'unit_price' => $detail['unit_price'],
                'quantity' => $detail['quantity'],
                'returned_quantity' => 0,
            ]);

            $product = $store->products->where('uuid', '=', $detail['uuid'])->first();
            $newQuantity = (string)((int) $product->quantity_available) + ((int) $detail['quantity']);
            $product->update(['quantity_available' => $newQuantity]);
            
            // update branch inventory table
            $productOfStore = BranchInventory::where([['branch_id', '=', $branch->id], ['product_id', '=', $product_id]])->first();

            if ($productOfStore) {
                BranchInventory::where([['branch_id', '=', $branch->id], ['product_id', '=', $product_id]])
                    ->increment('quantity_available', $detail['quantity']);
            } else {
                BranchInventory::create([
                    'store_id' => $store->id,
                    'branch_id' => $branch->id,
                    'product_id' => $product_id,
                    'quantity_available' => $detail['quantity'],
                ]);
            }
        }

        return response()->json([
            'message' => 'Purchase order created successfully',
            'data' => $purchaseOrder,
        ], 200);
    }

    public function store(Request $request, Store $store, Branch $branch)
    {
        $validated = $request->validate([
            'supplier_id' => 'required|numeric',
            'created_by' => 'required|numeric',
            'approved_by' => 'nullable|numeric',
            'creation_date' => 'required|date_format:Y-m-d',
            'approved_date' => 'nullable|date_format:Y-m-d',
            'payment_date' => 'nullable|date_format:Y-m-d',
            'payment_amount' => 'required|numeric',
            'payment_method' => 'nullable|string',
            'notes' => 'nullable|string',
            'taxes' => 'required|numeric',
            'status' => 'required|string'
        ]);

        $purchaseOrder = PurchaseOrder::create(array_merge($validated, [
            'store_id' => $store->id,
            'branch_id' => $branch->id
        ]));

        return response()->json([
            'message' => 'Purchase order created successfully',
            'data' => $purchaseOrder,
        ], 200);
    }


    public function update(Request $request, Store $store, Branch $branch, PurchaseOrder $purchaseOrder)
    {
        $validated = $request->validate([
            'approved_by' => 'nullable|numeric',
            'approved_date' => 'nullable|date_format:Y-m-d',
            'payment_date' => 'nullable|date_format:Y-m-d',
            'payment_amount' => 'nullable|numeric',
            'payment_method' => 'nullable|string',
            'notes' => 'nullable|string',
            'status' => 'nullable|string',
        ]);

        $purchaseOrder->update($validated);

        return response()->json([
            'message' => 'Purchase order updated successfully',
            'data' => $purchaseOrder,
        ], 200);
    }

    public function show(Store $store, PurchaseOrder $purchaseOrder)
    {

        $details = $purchaseOrder->purchaseOrderDetails()
            ->join('products', 'purchase_order_details.product_id', '=', 'products.id')
            ->select('purchase_order_details.*', 'products.name', 'products.bar_code')->get();

        if ($purchaseOrder->created_user_type === 'owner') {
            $created_by = User::where('id', $purchaseOrder->created_by)->first();
        } else {
            $created_by = Employee::where('id', $purchaseOrder->created_by)->first();
        }
        $data = array_merge([
            'supplier' => $purchaseOrder->supplier,
            'branch' => $purchaseOrder->branch,
            'details' => $details,
            'created_by_user' => $created_by,
        ], $purchaseOrder->toArray());

        return response()->json([
            'data' => $data
        ], 200);
    }
    
    public function destroy(Store $store, Branch $branch, PurchaseOrder $purchaseOrder)
    {
        $isdelected = PurchaseOrder::destroy($purchaseOrder->id);
        return response()->json([
            'message' => $isdelected,
            'data' => $purchaseOrder,
        ], 200);
    }
}
