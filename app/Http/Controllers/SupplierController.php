<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SupplierController extends Controller
{

    public function index(Store $store)
    {
        return response()->json([
            'data' => $store->suppliers()->where('status', '<>', 'deleted')->get(),
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, Store $store)
    {
        $validated = $request->validate([
            'company' => 'nullable|string',
            'name' => 'required|string',
            'email' => 'nullable|string',
            'job_title' => 'nullable|string',
            'phone' => 'nullable|string',
            'address' => 'nullable|string',
            'ward' => 'nullable|string',
            'city' => 'nullable|string',
            'province' => 'nullable|string',
            'payment_info' => 'nullable|string',
        ]);

        $supplier = Supplier::create(array_merge([
            'store_id' => $store->id,
            'uuid' => (string) Str::uuid()
        ],
            $validated
        ));

        return response()->json([
            'data' => $supplier
        ], 200);
    }

    public function show(Store $store, Supplier $supplier)
    {
        return response()->json([
            'data' => $supplier
        ], 200);
    }

    public function update(Request $request, Store $store, Supplier $supplier)
    {
        $validated = $request->validate([
            'company' => 'nullable|string',
            'name' => 'nullable|string',
            'email' => 'nullable|string',
            'job_title' => 'nullable|string',
            'phone' => 'nullable|string',
            'address' => 'nullable|string',
            'ward' => 'nullable|string',
            'city' => 'nullable|string',
            'province' => 'nullable|string',
            'payment_info' => 'nullable|string',
            'status' => 'nullable|string',
        ]);

        $supplier->update($validated);

        return response()->json([
            'data' => $supplier
        ], 200);
    }

    public function destroy(Store $store, Supplier $supplier)
    {
        $numOfSupplier = $store->suppliers()->where('status', 'active')->count();  
        if ($numOfSupplier <= 1) {
            return response()->json([
            'message' => 'Can not delete last supplier',
            'data' => $numOfSupplier
        ], 404);
        }


        $supplier->update(['status' => 'deleted']);
        return response()->json([
            'message' => 1,
            'data' => $numOfSupplier
        ], 200);
    }
}
