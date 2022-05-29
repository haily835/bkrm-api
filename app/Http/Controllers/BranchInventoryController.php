<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\BranchInventory;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

function Quartile($Array, $Quartile)
{
    sort($Array);
    $pos = (count($Array) - 1) * $Quartile;

    $base = floor($pos);
    $rest = $pos - $base;

    if (isset($Array[$base + 1])) {
        return $Array[$base] + $rest * ($Array[$base + 1] - $Array[$base]);
    } else {
        return $Array[$base];
    }
}

class BranchInventoryController extends Controller
{
    // transfer inventory between branch
    public function transferInventory(Request $request, Store $store, Branch $branch)
    {
        $validated = $request->validate([
            'to_id' => 'required|numeric',
            'to_name' => 'required|string',
            'value_quantity' => 'required|numeric',
            'batches' => 'nullable|string',
            'has_batches' => 'required|boolean',
            'product_id' => 'required|numeric',
            'created_user_name' => 'required|string',
            'created_user_type' => 'required|string',
            'created_user_id' => 'required|numeric',
            'created_at' => 'required'
        ]);

        # generate code
        $last_id = DB::table('transfer_inventory')->where('store_id', $store->id)->count();

        $code = 'CK' . sprintf('%06d', $last_id + 1);


        $transfer_inventory = [
            'product_id' => $validated['product_id'],
            'to_id' => $validated['to_id'],
            'from_id' => $branch['id'],
            'from_name' => $branch['name'],
            'to_name' => $validated['to_name'],
            'quantity' => $validated['value_quantity'],
            'from_batches' => $validated['batches'],
            'created_user_name' => $validated['created_user_name'],
            'created_user_type' => $validated['created_user_type'],
            'created_user_id' => $validated['created_user_id'],
            'code' => $code,
            'store_id' => $store->id,
            'status' => 'pending',
            'created_at' => $validated['created_at']
        ];




        DB::table('transfer_inventory')->insert($transfer_inventory);
        return response()->json([
            'transfer_inventory' => $transfer_inventory,
        ]);
    }

    public function index(Request $request, Store $store, Branch $branch)
    {
        $data = DB::table('transfer_inventory')
            ->where('from_id', $branch->id)
            ->orWhere('to_id', $branch->id)
            ->leftJoin('products', 'products.id', '=', 'transfer_inventory.product_id')
            ->orderBy('created_at', 'desc')
            ->select('products.status as product_status', 'products.*', 'transfer_inventory.*')
            ->get();
        return response()->json([
            'data' => $data
        ]);
    }

    public function update(Request $request, Store $store, Branch $branch, $id)
    {
        $validated = $request->all();

        if (array_key_exists('status', $validated) && $validated['status'] === 'closed') {
            BranchInventory::where('store_id', $store->id)
                ->where('branch_id', $validated['from_id'])
                ->where('product_id', $validated['product_id'])
                ->decrement('quantity_available', $validated['quantity']);

            BranchInventory::where('store_id', $store->id)
                ->where('branch_id', $validated['to_id'])
                ->where('product_id', $validated['product_id'])
                ->increment('quantity_available', $validated['quantity']);

            $to_batches = [];
            $batches = json_decode($validated['from_batches'], true);
            foreach ($batches as $batch) {
                DB::table('product_batches')
                ->where('store_id', $store->id)
                    ->where('branch_id', $validated['from_id'])
                    ->where('product_id', $validated['product_id'])
                    ->where('batch_code', $batch['batch_code'])
                    ->decrement('quantity', $batch['quantity']);

                $last_id = DB::table('product_batches')
                ->where('store_id', $store->id)
                    ->where('branch_id', $validated['to_id'])
                    ->where('product_id',  $validated['product_id'])
                    ->get()->count();
                $batch_code = 'L' . sprintf('%04d', $last_id + 1);


                DB::table('product_batches')
                ->insert([
                    'store_id' => $store->id,
                    'branch_id' => $validated['to_id'],
                    'product_id' =>  $validated['product_id'],
                    'quantity' => $batch['quantity'],
                    'expiry_date' => $batch['expiry_date'],
                    'batch_code' =>  $batch_code,
                    'position' => $batch['position']
                ]);

                array_push($to_batches, [
                    'batch_code' => $batch_code,
                    'expiry_date' => $batch['expiry_date'],
                    'position' => $batch['position'],
                    'from_batch' => $batch['batch_code'],
                    'quantity' => $batch['quantity'],
                ]);
            }


            DB::table('transfer_inventory')
                ->where('id', $id)
                ->update(['to_batches' => json_encode($to_batches)]);
        }


        $data = DB::table('transfer_inventory')
            ->where('id', $id)
            ->update(['status' => $validated['status']]);
        return response()->json([
            'data' => $data
        ]);
    }

    public function destroy(Request $request, Store $store, $id)
    {
        $transfer_inventory = DB::table('transfer_inventory')->where('id', $id)->first();

        BranchInventory::where('store_id', $store->id)
            ->where('branch_id', $transfer_inventory['from_id'])
            ->where('product_id', $transfer_inventory['product_id'])
            ->increment('quantity_available', $transfer_inventory['quantity']);

        BranchInventory::where('store_id', $store->id)
            ->where('branch_id', $transfer_inventory['to_id'])
            ->where('product_id', $transfer_inventory['product_id'])
            ->decrement('quantity_available', $transfer_inventory['quantity']);

        $from_batches = json_decode($transfer_inventory['from_batches'], true);
        foreach ($from_batches as $batch) {
            DB::table('product_batches')
                ->where('store_id', $store->id)
                ->where('branch_id', $transfer_inventory['from_id'])
                ->where('product_id', $transfer_inventory['product_id'])
                ->where('batch_code', $batch['batch_code'])
                ->increment('quantity', $batch['quantity']);
        }

        $to_batches = json_decode($transfer_inventory['to_batches'], true);
        foreach ($to_batches as $batch) {
            DB::table('product_batches')
                ->where('store_id', $store->id)
                ->where('branch_id', $transfer_inventory['to_id'])
                ->where('product_id', $transfer_inventory['product_id'])
                ->where('batch_code', $batch['batch_code'])
                ->decrement('quantity', $batch['quantity']);
        }
        $transfer_inventory = DB::table('transfer_inventory')->where('id', $id)->delete();
    }
}
