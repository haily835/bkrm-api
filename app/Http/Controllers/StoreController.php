<?php

namespace App\Http\Controllers;

use App\Models\Store;
use Illuminate\Http\Request;
use Intervention\Image\Facades\Image;

class StoreController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        Store::all();
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|unique:stores',
            'user_id' => 'required|numeric',
            'address' => 'required',
            'ward' => 'required',
            'city' => 'required',
            'province' => 'required',
            'phone' => 'required',
            'status' => 'required|in:active,inactive',
            'image' => '',
        ]);

        if ($request['image']) {
            $imagePath = $request['image']->store('store-images', 'public');

            $image = Image::make(public_path("storage/{$imagePath}"))->fit(1000, 1000);
            $image->save();

            $data['image'] = 'http://103.163.118.100/bkrm-api/' . $imagePath;
        } else {
            $data['image'] = 'http://103.163.118.100/bkrm-api/storage/store-images/store-default.png';
        }

        return Store::create($data);
        
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        return Store::find($id);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $store = Store::find($id);

        $data = $request->validate([
            'name' => 'required|unique:stores,name',
            'user_id' => 'required|numeric',
            'address' => 'required',
            'ward' => 'required',
            'city' => 'required',
            'province' => 'required',
            'phone' => 'required',
            'status' => 'required|in:active,inactive',
            'image'=> '',
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

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        return Store::destroy($id);
    }

    public function getStoreOfUser($user_id) {
        return Store::where('user_id', '=', $user_id)->get();
    }
}
