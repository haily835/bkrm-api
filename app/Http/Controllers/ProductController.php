<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Store;
use Intervention\Image\Facades\Image;

class ProductController extends Controller
{

    public function index(Store $store)
    {
        return $store->products()->paginate(2);
    }

    public function store(Request $request, Store $store)
    {
        $request->validate([
            'name' => ['required', 'string'],
            'list_price' => ['numeric', 'required'],
            'standard_price' => ['numeric', 'required'],
            'bar_code' => ['string', 'nullable', 'unique:products'],
            'quantity_per_unit' => ['string', 'required', 'nullable'],
            'store_id' => ['numeric', 'required'],
            'min_reorder_quantity' => ['numeric', 'required'],
            'image' => 'nullable|image',
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
                                    . 'storage/product-images/product-default.png';
        }

        return Product::create($data);
    }

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

        $product->update($data);

        return response()->json([
            'data' => $product,
        ], 200);
    }

    public function destroy(Product $product)
    {
        return Product::destroy($product->id);
    }
}
