<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;

class SupplierController extends Controller
{

    public function index(Store $store)
    {
        return response()->json([
            'data' => $store->suppliers()
                ->where('status', '<>', 'deleted')
                ->get(),
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
            'image' => 'nullable',
        ]);

        $imagePath = "";
        if (array_key_exists('image', $validated)) {
            if ($validated['image'] != null) {
                $imagePath = $validated['image']->store('supplier-images', 'public');
                $sized_image = Image::make(public_path("storage/{$imagePath}"))->fit(1000, 1000);
                $sized_image->save();
                $imagePath = 'http://103.163.118.100/bkrm-api/storage/app/public/' . $imagePath;
            }
        } else {
            $imagePath = 'http://103.163.118.100/bkrm-api/storage/app/public/supplier-images/supplier-default.png';
        }

        $supplier = Supplier::create(
            [
                'store_id' => $store->id,
                'uuid' => (string) Str::uuid(),
                'img_url' => $imagePath,
                'job_title' => array_key_exists('job_title', $validated) ? $validated['job_title'] : "",
                'email' => array_key_exists('email', $validated) ? $validated['email'] : "",
                'name' => array_key_exists('name', $validated) ? $validated['name'] : "",
                'address' => array_key_exists('address', $validated) ? $validated['address'] : "",
                'phone' => array_key_exists('phone', $validated) ? $validated['phone'] : "",
                'ward' => array_key_exists('ward', $validated) ? $validated['ward'] : "",
                'city' => array_key_exists('city', $validated) ? $validated['city'] : "",
                'province' => array_key_exists('province', $validated) ? $validated['province'] : "",
                'payment_info' => array_key_exists('payment_info', $validated) ? $validated['payment_info'] : "",
                'company' => array_key_exists('company', $validated) ? $validated['company'] : "",
            ],
        );

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
