<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Store;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class BranchController extends Controller
{

    public function index(Store $store)
    {
        return response()->json([
            'data' => $store->branches, 
        ]);
    }

    public function store(Request $request, Store $store)
    {
        $data = $request->validate([
            'name' => 'required|unique:stores',
            'address' => 'nullable|string',
            'ward' => 'nullable|string',
            'city' => 'nullable|string',
            'province' => 'nullable|string',
            'phone' => 'nullable|string',
            'status' => 'nullable|in:active,inactive',
        ]);

        
        $branch = Branch::create(array_merge($data, [
            'store_id' => $store->id,
            'uuid' => (string) Str::uuid(),
        ]));

        return response()->json([
            'message' => 'Branch created successfully',
            'data' => $branch,
        ], 200);
    }

    public function show(Store $store, Branch $branch)
    {
        return response()->json([
            'data' => $branch,
        ], 200);
        
    }

    public function update(Request $request, Store $store, Branch $branch)
    {
        $data = $request->validate([
            'name' => 'nullable|unique:stores',
            'address' => 'nullable|string',
            'ward' => 'nullable|string',
            'city' => 'nullable|string',
            'province' => 'nullable|string',
            'phone' => 'nullable|string',
            'status' => 'nullable|in:active,inactive',
        ]);

        $branch->update($data);

        return response()->json([
            'message' => 'Branch update successfully',
            'data' => $branch,
        ], 200);
    }

    public function destroy(Store $store, Branch $branch)
    {
        return response()->json([
            'message' => 'Branch deleted successfully',
            'data' => $branch,
        ], 200);
    }
}
