<?php

namespace App\Http\Controllers;

use App\Models\Store;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Http\Request;
use Intervention\Image\Facades\Image;

class StoreController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::guard('user')->user();
        $store = Store::where('user_id', $user->id)->get();
        return response()->json([
            'message' => '',
            'data' => $store,
            'user' => $user,
        ], 200);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|unique:stores',
            'user_id' => 'required|numeric',
            'address' => 'required|string',
            'ward' => 'required|string',
            'city' => 'required|string',
            'province' => 'required|string',
            'phone' => 'required|string',
            'status' => 'required|in:active,inactive',
            'image' => 'nullable|image',
        ]);

        if ($request['image']) {
            $imagePath = $request['image']->store('store-images', 'public');

            $image = Image::make(public_path("storage/{$imagePath}"))->fit(1000, 1000);
            $image->save();

            $data['image'] = 'http://103.163.118.100/bkrm-api/storage/app/public/' . $imagePath;
        } else {
            $data['image'] = 'http://103.163.118.100/bkrm-api/storage/app/public/store-images/store-default.png';
        }

        $store = Store::create($data);

        return response()->json([
            'message' => 'Store created successfully',
            'store' => $store,
        ], 200);
    }

    public function update(Request $request, Store $store)
    {
        $data = $request->validate([
            'name' => 'nullable|unique:stores,name',
            'address' => 'nullable|string',
            'ward' => 'nullable|string',
            'city' => 'nullable|string',
            'province' => 'nullable|string',
            'phone' => 'nullable|string',
            'status' => 'nullable|string|in:active,inactive',
            'image'=> 'nullable|image',
        ]);

        if ($request['image']) {
            $imagePath = $request['image']->store('store-images', 'public');

            $image = Image::make(public_path("storage/{$imagePath}"))->fit(1000, 1000);
            $image->save();

            $data['image'] = $imagePath;
        } else {
            $data['image'] = 'storage/store-images/store-default.png';
        }

        $store->update($data);
        return $store;
    }

    public function destroy(Store $store)
    {
        return Store::destroy($store->id);
    }
}
