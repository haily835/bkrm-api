<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Store;
use App\Models\Supplier;
use App\Models\Category;
use App\Models\ProductSupplier;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{

    public function index(Store $store)
    {
        return $store->products()->paginate(20);
    
    }

    public function store(Request $request, Store $store)
    {
        $data = $request->validate([
            'name' => ['required', 'string'],
            'list_price' => ['numeric', 'required'],
            'standard_price' => ['numeric', 'required'],
            'category_uuid' => ['string', 'required'],
            'bar_code' => ['string', 'nullable', 'unique:products'],
            'quantity_per_unit' => ['string', 'nullable'],
            'min_reorder_quantity' => ['numeric', 'nullable'],
            'images' => 'nullable',
            'description' => 'string|nullable',
        ]);

        $imageUrls = array();
        $product_uuid = (string) Str::uuid();

        if (array_key_exists('images', $data)) {
            if ($data['images'] != null) {
                foreach ($data['images'] as $image) {
                    $imagePath = $image->store('product-images', 'public');
    
                    $sized_image = Image::make(public_path("storage/{$imagePath}"))->fit(1000, 1000);
                    $sized_image->save();
                    $imageUrl = 'http://103.163.118.100/bkrm-api/storage/app/public/'
                        . $imagePath;
    
                    DB::table('images')->insert([
                        'uuid' => (string) Str::uuid(),
                        'url' => $imageUrl,
                        'store_id' => $store->id,
                        'entity_uuid' => $product_uuid,
                        'image_type' => 'product'
                    ]);
    
                    array_push($imageUrls, $imageUrl);
                }
            }
        } else {
            DB::table('images')->insert([
                'uuid' => (string) Str::uuid(),
                'url' => 'http://103.163.118.100/bkrm-api/storage/app/public/'
                    . 'storage/product-images/product-default.png',
                'store_id' => $store->id,
                'entity_uuid' => $product_uuid,
                'image_type' => 'product'
            ]);

            array_push($imageUrls, 'http://103.163.118.100/bkrm-api/storage/app/public/'
                . 'storage/product-images/product-default.png');
        }

        $category = Category::where('uuid', $data['category_uuid'])->first();

        $newProduct =  Product::create([
            'store_id' => $store->id,
            'uuid' => $product_uuid,
            'category_id' => $category->id,
            'name' => $data['name'],
            'list_price' => $data['list_price'],
            'standard_price' => $data['standard_price'],
            'bar_code' => array_key_exists('bar_code', $data) ? $data['bar_code'] : "",
            'quantity_per_unit' => array_key_exists('quantity_per_unit', $data) ? $data['quantity_per_unit'] : "cái",
            'min_reorder_quantity' => array_key_exists('min_reorder_quantity', $data) ? $data['min_reorder_quantity'] : 100,
            'description' => array_key_exists('description', $data) ? $data['description'] : "",
            'quantity_available' => '0',
            'has_variance' => false,
            'on_sale' => false,
        ]);

        return response()->json([
            'message' => "Product created successfully",
            'data' => $newProduct,
            'product_images' => $imageUrls,
        ], 200);
    }

    public function show(Request $request, Store $store, Product $product)
    {
        $category = $product->category;
        $images = DB::table('images')->where('entity_uuid', $product['uuid'])->get('url');
        $suppliers = $product->suppliers->get('name');

        $data = array_merge($product->toArray(), [
            'category' => $category,
            'images' => $images,
            'suppliers' => $suppliers,
        ]);

        return response()->json([
            'data' => $data,
        ], 200);
    }

    public function update(Request $request, Store $store, Product $product)
    {
        $request->validate([
            'name' => ['nullable', 'string'],
            'list_price' => ['numeric', 'nullable'],
            'category_uuid' => ['string', 'required'],
            'standard_price' => ['numeric', 'nullable'],
            'bar_code' => ['string', 'nullable', 'unique:products'],
            'quantity_per_unit' => ['string', 'nullable', 'nullable'],
            'min_reorder_quantity' => ['numeric', 'nullable'],
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

    public function destroy(Store $store, Product $product)
    {
        $isDeleted = Product::destroy($product->id);
        return response()->json([
            'message' => $isDeleted,
            'data' => $product
        ], 200);
    }


    public function suppliers(Store $store, Product $product)
    {
        $product_supplier = ProductSupplier::where('product_id', $product->id)->get();

        return response()->json([
            'data' => $product_supplier
        ], 200);
    }

    public function addSupplier(Request $request, Store $store, Product $product)
    {
        $validated = $request->validate([
            'supplier_uuid' => 'required|string'
        ]);

        $supplier_id = Supplier::where('uuid', $validated['supplier_uuid'])->first()->id;

        $product_supplier = ProductSupplier::create([
            'supplier_id' => $supplier_id,
            'product_id' => $product->id,
            'store_id' => $store->id,
        ]);

        return response()->json([
            'data' => $product_supplier
        ], 200);
    }

    public function deleteSupplier(Request $request, Store $store, Product $product, Supplier $supplier)
    {

        $product_supplier = ProductSupplier::where('product_id', $product->id)
            ->where('suplier_id', $supplier->id);

        $isDeleted = ProductSupplier::destroy($product_supplier->id);

        return response()->json([
            'message' => $isDeleted,
            'data' => $product_supplier
        ], 200);
    }
}
