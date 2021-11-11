<?php

namespace App\Http\Controllers;

use App\Models\Store;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CustomerController extends Controller
{
    public function index(Store $store)
    {
        return response()->json([
            'data' => $store->customers,
        ], 200);
    }

 
    public function store(Request $request, Store $store)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'phone' => 'nullable|string',
            'address' => 'nullable|string',
            'ward' => 'nullable|string',
            'city' => 'nullable|string',
            'province' => 'nullable|string',
            'payment_info' => 'nullable|string',
        ]);

        $supplier = Customer::create(array_merge([
            'store_id' => $store->id,
            'uuid' => (string) Str::uuid()
        ],
            $validated
        ));

        return response()->json([
            'data' => $supplier
        ], 200);
    }

    public function show(Store $store, Customer $customer)
    {
        return response()->json([
            'data' => $customer
        ], 200);
    }

    public function update(Request $request, Store $store, Customer $customer)
    {
        $validated = $request->validate([
            'name' => 'nullable|string',
            'phone' => 'nullable|string',
            'address' => 'nullable|string',
            'ward' => 'nullable|string',
            'city' => 'nullable|string',
            'province' => 'nullable|string',
            'payment_info' => 'nullable|string',
        ]);

        $customer->update($validated);

        return response()->json([
            'data' => $customer
        ], 200);
    }

    public function destroy(Store $store, Customer $customer)
    {
        $isdeleted = Customer::destroy($customer->id);
        return response()->json([
            'message' => $isdeleted,
            'data' => $customer
        ], 200);
    }
}
