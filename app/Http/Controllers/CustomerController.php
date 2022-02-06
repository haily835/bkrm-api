<?php

namespace App\Http\Controllers;

use App\Models\Store;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;

class CustomerController extends Controller
{
    public function index(Store $store)
    {
        return response()->json([
            'data' => $store->customers()->where('status', '<>', 'deleted')->get(),
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
            'image' => 'nullable'
        ]);

        $imagePath = "";
        if (array_key_exists('image', $validated)) {
            if ($validated['image'] != null) {
                $imagePath = $validated['image']->store('customer-images', 'public');
                $sized_image = Image::make(public_path("storage/{$imagePath}"))->fit(1000, 1000);
                $sized_image->save();
            }
        } else {
            $imagePath = 'http://103.163.118.100/bkrm-api/storage/app/public/customer-images/customer-default.png';
        }

        $supplier = Customer::create(array_merge(
            [
                'store_id' => $store->id,
                'uuid' => (string) Str::uuid(),
                'img_url' => $imagePath,
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
            'status' => 'nullable|string',
        ]);

        $customer->update($validated);

        return response()->json([
            'data' => $customer
        ], 200);
    }

    public function destroy(Store $store, Customer $customer)
    {
        $numOfCust = $store->customers()->where('status', 'active')->count();
        if ($numOfCust <= 1) {
            return response()->json([
                'message' => 'Can not delete last customer',
                'data' => $customer
            ], 404);
        }

        $customer->update(['status' => 'deleted']);
        return response()->json([
            'message' => 1,
            'data' => $customer
        ], 200);
    }
}
