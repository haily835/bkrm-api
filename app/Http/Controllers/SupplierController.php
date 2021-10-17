<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use App\Models\Store;
use Illuminate\Http\Request;

class SupplierController extends Controller
{

    public function index(Request $request)
    {

        $store_id = $request->query('store_id');

        if (Store::where('id', $store_id)->doesntExist()) {
            return response()->json(['message' => 'store_id do not exist'], 404);
        }

        
        return Supplier::where('store_id', $store_id)->get();
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'company' => 'required|string',
            'last_name' => 'nullable|string',
            'first_name' => 'nullable|string',
            'email' => 'nullable|string',
            'job_title' => 'nullable|string',
            'phone' => 'nullable|string',
            'address' => 'nullable|string',
            'ward' => 'nullable|string',
            'city' => 'nullable|string',
            'province' => 'nullable|string',
            'payment_info' => 'required|in:active,inactive',
            'store_id' => 'required|numeric',
        ]);

        if (Store::where('id', $request['store_id'])->doesntExist()) {
            return response()->json(['message' => 'store_id do not exist']);
        }

        return Supplier::create($request->all());
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Supplier  $supplier
     * @return \Illuminate\Http\Response
     */
    public function show(Supplier $supplier)
    {
        return $supplier;
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Supplier  $supplier
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Supplier $supplier)
    {
        $request->validate([
            'company' => 'required|string',
            'last_name' => 'nullable|string',
            'first_name' => 'nullable|string',
            'email' => 'nullable|string',
            'job_title' => 'nullable|string',
            'phone' => 'nullable|string',
            'address' => 'nullable|string',
            'ward' => 'nullable|string',
            'city' => 'nullable|string',
            'province' => 'nullable|string',
            'payment_info' => 'required|in:active,inactive',
            'store_id' => 'required|numeric',
        ]);

        if (Store::where('id', $request['store_id'])->doesntExist()) {
            return response()->json(['message' => 'store_id do not exist']);
        }

        return $supplier->update($request->all());
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Supplier  $supplier
     * @return \Illuminate\Http\Response
     */
    public function destroy(Supplier $supplier)
    {
        return Supplier::destroy($supplier->id);
    }

}
