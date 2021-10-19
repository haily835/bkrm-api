<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use App\Models\Store;
use Illuminate\Http\Request;

class SupplierController extends Controller
{

    public function index(Store $store)
    {
        return response()->json([
            'data' => $store->suppliers,
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

        $supplier = Supplier::create(array_merge(
            ['store_id' => $store->id],
            $validated
        ));

        return response()->json([
            'data' => $supplier
        ], 200);
    }

    public function show(Supplier $supplier)
    {
        return $supplier;
    }

    public function update(Request $request, Supplier $supplier)
    {
        $validated = $request->validate([
            'company' => 'nullable|string',
            'last_name' => 'nullable|string',
            'first_name' => 'nullable|string',
            'email' => 'nullable|string',
            'job_title' => 'nullable|string',
            'phone' => 'nullable|string',
            'address' => 'nullable|string',
            'ward' => 'nullable|string',
            'city' => 'nullable|string',
            'province' => 'nullable|string',
            'payment_info' => 'nullable|string',
        ]);

        $supplier->update($validated);

        return response()->json([
            'data' => $supplier
        ], 200);
    }

    public function destroy(Supplier $supplier)
    {
        return Supplier::destroy($supplier->id);
    }

}
