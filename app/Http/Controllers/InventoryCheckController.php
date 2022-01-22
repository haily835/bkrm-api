<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Store;
use App\Models\Branch;
use App\Models\InventoryCheck;
use App\Models\InventoryCheckDetail;
use App\Models\InventoryTransaction;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Employee;
use Illuminate\Support\Str;
use App\Models\BranchInventory;

class InventoryCheckController extends Controller
{
    public function index(Request $request, Store $store, Branch $branch)
    {
        // extract query string
        $start_date = $request->query('startDate');
        $end_date = $request->query('endDate');
        $status = $request->query('status');
        $created_user_type = $request->query('created_user_type');
        $user_name = $request->query('user_name');
        $inventory_check_code = $request->query('inventory_check_code');

        // set up query
        $queries = [];

        if ($inventory_check_code) {
            array_push($queries, ['inventory_checks.inventory_check_code', 'LIKE', $inventory_check_code]);
        }

        if ($start_date) {
            array_push($queries, ['inventory_checks.created_at', '>=', $start_date . ' 00:00:00']);
        }

        if ($end_date) {
            array_push($queries, ['inventory_checks.created_at', '<=', $end_date . ' 12:59:59']);
        }

        $inventoryChecks = $branch->inventoryChecks()
            ->where($queries)
            ->join('branches', 'inventory_checks.branch_id', '=', 'branches.id')
            ->select('inventory_checks.*', 'branches.name as branch_name')->get();

        return response()->json([
            'data' => $inventoryChecks,
        ], 200);
    }

    public function store(Request $request, Store $store, Branch $branch)
    {
        $validated = $request->validate([
            'total_amount' => 'required|number',
            'details' => 'required',
            'note' => 'nullable',
        ]);

        // get the user of  token
        $created_by = $approved_by = null;
        $created_user_type = '';

        if (Auth::guard('user')->user()) {
            $created_by = $approved_by = Auth::guard('user')->user()->id;
            $created_user_type = 'owner';
        } else if (Auth::guard('employee')->user()) {
            $created_by = $approved_by = Auth::guard('employee')->user()->id;
            $created_user_type = 'employee';
        } else {
            return response()->json([
                'message' => 'Unauthorized',
            ], 401);
        }

        $last_id = $store->invetoryChecks()->count();

        $invetoryCheckCode = 'KK' . sprintf('%06d', $last_id + 1);

        $invetoryCheck = InventoryCheck::create([
            'store_id' => $store->id,
            'uuid' => (string)Str::uuid(),
            'branch_id' => $branch->id,
            'inventory_check_code' => $invetoryCheckCode,
            'approved_by' => $approved_by,
            'created_by' => $created_by,
            'total_amount' => $validated['total_amount'],
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
                'transaction_type' => 'balance',
            ]);

            InventoryCheck::create([
                'store_id' => $store->id,
                'branch_id' => $branch->id,
                'product_id' => $product_id,
                'inventory_transaction_id' => $inventoryTransaction->id,
                'purchase_order_id' => $invetoryCheck->id,
                'unit_price' => $detail['unit_price'],
                'quantity' => $detail['quantity'],
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
            'message' => 'Inventory check created successfully',
            'data' => $invetoryCheck,
        ], 200);
    }

    public function show(Store $store, InventoryCheck $inventoryCheck)
    {

        $details = $inventoryCheck->inventoryCheckDetails()
            ->join('products', 'inventory_check_details.product_id', '=', 'products.id')
            ->select('inventory_check_details.*', 'products.name', 'products.bar_code')->get();

        if ($inventoryCheck->created_user_type === 'owner') {
            $created_by = User::where('id', $inventoryCheck->created_by)->first();
        } else {
            $created_by = Employee::where('id', $inventoryCheck->created_by)->first();
        }
        $data = array_merge([
            'branch' => $inventoryCheck->branch,
            'details' => $details,
            'created_by_user' => $created_by,
        ], $inventoryCheck->toArray());

        return response()->json([
            'data' => $data
        ], 200);
    }
}
