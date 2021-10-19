<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Store;
use Illuminate\Http\Request;

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

        
        $branch = $store->branches->create($data);

        return response()->json([
            'message' => 'Branch created successfully',
            'data' => $branch,
        ], 200);
    }

    public function show(Branch $branch)
    {
        return $branch;
    }

    public function update(Request $request, Branch $branch)
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

    public function destroy(Branch $branch)
    {
        return Branch::destroy($branch->id);
    }
}
