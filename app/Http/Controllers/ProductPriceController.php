<?php

namespace App\Http\Controllers;

use App\Models\ProductPrice;
use App\Models\Store;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductPriceController extends Controller
{
    public function index(Request $request)
    {
        $store_id = $request->query('store_id');
        $product_id = $request->query('product_id');

        if (Store::where('id', $store_id)->doesntExist()) {
            return response()->json(['message' => 'store_id do not exist'], 404);
        }

        if (Product::where('id', $product_id)->doesntExist()) {
            return response()->json(['message' => 'product_id do not exist'], 404);
        }

        return ProductPrice::where('store_id', $store_id)
            ->where('product_id', $product_id)->get();
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        return ProductPrice::create($request->all());
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\ProductPrice  $productPrice
     * @return \Illuminate\Http\Response
     */
    public function show(ProductPrice $productPrice)
    {
        return $productPrice;
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\ProductPrice  $productPrice
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, ProductPrice $productPrice)
    {
        return $productPrice->update($request->all());
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\ProductPrice  $productPrice
     * @return \Illuminate\Http\Response
     */
    public function destroy(ProductPrice $productPrice)
    {
        return ProductPrice::destroy($productPrice->id);
    }
}
