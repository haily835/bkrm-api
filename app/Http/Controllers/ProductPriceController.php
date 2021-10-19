<?php

namespace App\Http\Controllers;

use App\Models\ProductPrice;
use App\Models\Store;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductPriceController extends Controller
{
    public function index(Request $request, Store $store, Product $product)
    {
        return ProductPrice::where('store_id', $store->id)
            ->where('product_id', $product->id)->get();
    }

    public function store(Request $request, Store $store, Product $product)
    {
        $validated = $request->validate([
            'price' => 'required|numeric',
            'start_date' => 'required|datetime',
            'end_date' => 'required|datetime',
        ]);

        $productPrice = array_merge([
            'product_id' => $product->id,
            'store_id' => $store->id,
        ], $validated);

        ProductPrice::create($productPrice);
        return response()->json([
            'message' => 'Product price create successfully',
            'data' => $productPrice
        ], 200);
    }

    public function update(Request $request, Store $store, Product $product, ProductPrice $productPrice)
    {
        $validated = $request->validate([
            'price' => 'nullable|numeric',
            'start_date' => 'nullable|datetime',
            'end_date' => 'nullable|datetime',
        ]);

        $newProductPrice = array_merge([
            'product_id' => $product->id,
            'store_id' => $store->id,
        ], $validated);

        $productPrice->update($newProductPrice);

        return response()->json([
            'message' => 'Product price update successfully',
            'data' => $productPrice
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\ProductPrice  $productPrice
     * @return \Illuminate\Http\Response
     */
    public function destroy(Store $store, Product $product, ProductPrice $productPrice)
    {
        return ProductPrice::destroy($productPrice->id);
    }
}
