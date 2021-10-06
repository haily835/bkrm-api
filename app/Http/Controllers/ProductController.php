<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use Intervention\Image\Facades\Image;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if (Store::where('store_id', $request['store_id'])->doesntExist()) {
            return response()->json(['message' => 'store_id do not exist']);
        }

        $store_id = $request['store_id'];
            return Product::where('store_id', $store_id)->get();
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
            'name' => ['required', 'string'],
            'list_price' => ['numeric', 'required'],
            'standard_price' => ['numeric', 'required'],
            'bar_code' => ['string', 'nullable', 'unique:products'],
            'quantity_per_unit' => ['string', 'required', 'nullable'],
            'store_id' => ['numeric', 'required'],
            'min_reorder_quantity' => ['numeric', 'required'],
            'image' => '',
        ]);


        if (Store::where('id', $request['store_id'])->doesntExist()) {
            return response()->json(['message' => 'store_id do not exist']);
        }

        $data = $request->all();


        if ($request['image']) {
            if (strcmp(gettype($request['image']), 'string')) {
                $data['image'] = $request['image'];
            } else {
                $imagePath = $request['image']->store('product-images', 'public');
    
                $image = Image::make(public_path("storage/{$imagePath}"))->fit(1000, 1000);
                $image->save();
    
                $data['image'] = 'http://103.163.118.100/bkrm-api/storage/app/public/' 
                                    . $imagePath;
            }

        } else {
            $data['image'] = 'http://103.163.118.100/bkrm-api/storage/app/public/' 
                                    . 'storage/store-images/store-default.png';
        }

        return Product::create($data);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Product $product)
    {
        return $product;
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Product $product)
    {
        $request->validate([
            'name' => ['required', 'string'],
            'list_price' => ['numeric', 'required'],
            'standard_price' => ['numeric', 'required'],
            'bar_code' => ['string', 'nullable', 'unique:products'],
            'quantity_per_unit' => ['string', 'required', 'nullable'],
            'store_id' => ['numeric', 'required'],
            'min_reorder_quantity' => ['numeric', 'required'],
            'image' => '',
        ]);

        if (Store::where('id', $request['store_id'])->doesntExist()) {
            return response()->json(['message' => 'store_id do not exist']);
        }

        $data = $request->all();
        if ($request['image']) {
            if (strcmp(gettype($request['image']), 'string')) {
                $data['image'] = $request['image'];
            } else {
                $imagePath = $request['image']->store('product-images', 'public');
    
                $image = Image::make(public_path("storage/{$imagePath}"))->fit(1000, 1000);
                $image->save();
    
                $data['image'] = 'http://103.163.118.100/bkrm-api/storage/app/public/' 
                                    . $imagePath;
            }

        } else {
            $data['image'] = 'http://103.163.118.100/bkrm-api/storage/app/public/' 
                                    . 'storage/store-images/store-default.png';
        }
        return $product->update($data);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Product $product)
    {
        return Product::destroy($product->id);
    }

}
