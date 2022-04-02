<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\BranchInventory;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BranchInventoryController extends Controller
{
    // transfer inventory between branch
    public function transferInventory(Request $request, Store $store, Branch $branch)
    {
        $validated = $request->validate([
            'to_branch' => 'required|numeric',
            'value_quantity' => 'required|numeric',
            'batches' => 'nullable|string',
            'has_batches' => 'required|boolean',
            'product_id' => 'required|numeric'
        ]);

        BranchInventory::where('store_id', $store->id)
            ->where('branch_id', $branch->id)
            ->where('product_id', $validated['product_id'])
            ->decrement('quantity_available', $validated['value_quantity']);

        BranchInventory::where('store_id', $store->id)
            ->where('branch_id', $validated['to_branch'])
            ->where('product_id', $validated['product_id'])
            ->increment('quantity_available', $validated['value_quantity']);

        if ($validated['has_batches']) {
            $batches = json_decode($validated['batches'], true);
            foreach ($batches as $batch) {
                DB::table('product_batches')
                    ->where('store_id', $store->id)
                    ->where('branch_id', $branch->id)
                    ->where('product_id', $validated['product_id'])
                    ->where('batch_code', $batch['batch_code'])
                    ->decrement('quantity', $batch['quantity']);

                $last_id = DB::table('product_batches')
                    ->where('store_id', $store->id)
                    ->where('branch_id', $validated['to_branch'])
                    ->where('product_id',  $validated['product_id'])
                    ->get()->count();
                $batch_code = 'L' . sprintf('%04d', $last_id + 1);

                DB::table('product_batches')
                    ->insert([
                        'store_id' => $store->id,
                        'branch_id' => $validated['to_branch'],
                        'product_id' =>  $validated['product_id'],
                        'quantity' => $batch['quantity'],
                        'expiry_date' => $batch['expiry_date'],
                        'batch_code' =>  $batch_code,
                        'position' => $batch['position']
                    ]);
            }
        }
    }
}
