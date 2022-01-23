<?php

namespace App\Http\Controllers;

use App\Models\BranchInventory;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Store;
use App\Models\Supplier;
use App\Models\Category;
use App\Models\ProductSupplier;
use App\Models\Branch;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use App\Models\Barcode;

class ProductController extends Controller
{
    public function indexOfBranch(Request $request, Store $store, Branch $branch)
    {
        $search_key = $request->query("searchKey");

        $products = [];

        if($search_key) {
            $products = $store->products()
                ->where('store_id', $store->id)
                ->where('name', 'LIKE', '%' . $search_key . '%')
                // ->orWhere('bar_code', 'LIKE','%' . $search_key . '%')
                ->where('status', '<>', 'inactive')
                ->where('status', '<>', 'deleted')
                ->get()->toArray();
        } else {
            $products = $store->products()
                ->where('status', '<>', 'deleted')
                ->get()->toArray();
        }

        $data = [];

        foreach($products as $product) {
            $firstImageUrl = DB::table('images')->where('entity_uuid', $product['uuid'])->get('url')->first();
            $category = $store->categories->where('id', $product['category_id'])->first();
            unset($product['category_id']);

            // get branch inventory of that product
            $branch_product = $branch->inventory()->where('product_id', $product['id'])->first();

            if($branch_product) {
                $branch_quantity = $branch_product->quantity_available;
            } else {
                BranchInventory::create([
                    'store_id' => $store->id,
                    'branch_id' => $branch->id,
                    'product_id' => $product['id'],
                    'quantity_available' => 0,
                ]);
                $branch_quantity = 0;
            }

            array_push($data, array_merge($product, [
                'img_url' => $firstImageUrl->url,
                'category' => $category,
                'branch_quantity' => $branch_quantity,
            ]));
        }

        return response()->json([
            'data' => $data,
        ], 200);
    }

    public function index(Request $request, Store $store)
    {
        $search_key = $request->query("searchKey");

        $products = [];

        if($search_key) {
            $products = $store->products()->where('name', 'LIKE', '%' . $search_key . '%')
                                        ->orWhere('bar_code', 'LIKE','%' . $search_key . '%')
                                        ->where('status', '<>', 'inactive')
                                        ->where('status', '<>', 'deleted')
                                        ->get()->toArray();
        } else {
            $products = $store->products()
                ->where('status', '<>', 'deleted')
                ->get()->toArray();
        }

        $data = [];

        foreach($products as $product) {
            $firstImageUrl = DB::table('images')->where('entity_uuid', $product['uuid'])->get('url')->first();
            $category = $store->categories->where('id', $product['category_id'])->first();
            unset($product['category_id']);
            array_push($data, array_merge($product, [
                'img_url' => $firstImageUrl->url,
                'category' => $category,
            ]));
        }

        return response()->json([
            'data' => $data,
        ], 200);
    }

    public function searchBranchInventory(Request $request, Store $store, Branch $branch)
    {
        $search_key = $request->query("searchKey");

        $products = [];

        if($search_key) {
            $products = $store->products()
                ->where('name', 'LIKE', '%' . $search_key . '%')
                // ->orWhere('bar_code', 'LIKE','%' . $search_key . '%')
                ->get()
                ->toArray();
        } else {
            $products = $store->products()->get()->toArray();
        }

        $data = [];

        foreach($products as $product) {
            $firstImageUrl = DB::table('images')->where('entity_uuid', $product['uuid'])->get('url')->first();
            $category = $store->categories->where('id', $product['category_id'])->first();
            unset($product['category_id']);

            // get branch inventory of that product
            $branch_product = $branch->inventory()->where('product_id', $product['id'])->first();

            if($branch_product) {
                $branch_quantity = $branch_product->quantity_available;
            } else {
                BranchInventory::create([
                    'store_id' => $store->id,
                    'branch_id' => $branch->id,
                    'product_id' => $product['id'],
                    'quantity_available' => 0,
                ]);
                $branch_quantity = 0;
            }

            array_push($data, array_merge($product, [
                'img_url' => $firstImageUrl->url,
                'category' => $category,
                'branch_quantity' => $branch_quantity,
            ]));
        }

        return response()->json([
            'data' => $data,
        ], 200);
    }

    public function store(Request $request, Store $store)
    {
        $data = $request->validate([
            'name' => ['required', 'string'],
            'list_price' => ['numeric', 'required'],
            'standard_price' => ['numeric', 'required'],
            'category_uuid' => ['string', 'required'],
            'bar_code' => ['string', 'nullable'],
            'quantity_per_unit' => ['string', 'nullable'],
            'min_reorder_quantity' => ['numeric', 'nullable'],
            'images' => 'nullable',
            'description' => 'string|nullable',
            'img_url' => 'nullable|string'
        ]);
        
        $product_uuid = (string) Str::uuid();

        $imageUrls = array();

        if (array_key_exists('img_url', $data)) {
            if ($data['img_url']) {
                DB::table('images')->insert([
                    'uuid' => (string) Str::uuid(),
                    'url' => $data['img_url'],
                    'store_id' => $store->id,
                    'entity_uuid' => $product_uuid,
                    'image_type' => 'product'
                ]);
            }
            array_push($imageUrls, $data['img_url']);
        }
        
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

        
        $barcode = "";
        // create barcode if user not specify
        if (array_key_exists('bar_code', $data)) {
            if($data['bar_code']) {
                $barcode = $data['bar_code'];
            } else {
                $last_id = count($store->products);
                $barcode = 'SP' . sprintf( '%04d', $last_id );
            }
        } else {
            $last_id = count($store->products);
            $barcode = 'SP' . sprintf( '%04d', $last_id );
        }

        $newProduct =  Product::create([
            'store_id' => $store->id,
            'uuid' => $product_uuid,
            'category_id' => $category->id,
            'name' => $data['name'],
            'list_price' => $data['list_price'],
            'standard_price' => $data['standard_price'],
            'bar_code' => $barcode,
            'quantity_per_unit' => array_key_exists('quantity_per_unit', $data) ? $data['quantity_per_unit'] : "cái",
            'min_reorder_quantity' => array_key_exists('min_reorder_quantity', $data) ? $data['min_reorder_quantity'] : 100,
            'description' => array_key_exists('description', $data) ? $data['description'] : "",
            'quantity_available' => '0',
            'has_variance' => false,
            'on_sale' => false,
        ]);
        
        // add new product to each branch inventory
        foreach($store->branches as $branch) {
            BranchInventory::create([
                'store_id' => $store->id,
                'branch_id' => $branch->id,
                'product_id' => $newProduct->id,
                'quantity_available' => 0,
            ]);
        }

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

    public function editProduct(Request $request, Store $store, Product $product) {
        return $request->all();
    }

    public function update(Request $request, Store $store, Product $product)
    {
        $data = $request->validate([
            'name' => ['nullable', 'string'],
            'list_price' => ['numeric', 'nullable'],
            'standard_price' => ['numeric', 'nullable'],
            'category_uuid' => ['string', 'nullable'],
            'bar_code' => ['string', 'nullable'],
            'quantity_per_unit' => ['string', 'nullable'],
            'min_reorder_quantity' => ['numeric', 'nullable'],
            'description' => 'string|nullable',
            'deleted_urls' => 'nullable|array',
            'new_images' => 'nullable',
        ]);

        if (isset($data['deleted_urls'])) {
            if($data['deleted_urls']) {
                DB::table('images')->where('entity_uuid', $product['uuid'])
                    ->whereIn('url', $data['deleted_urls'])->delete();
            }
        }

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
                        'entity_uuid' => $product->uuid,
                        'image_type' => 'product'
                    ]);

                    array_push($imageUrls, $imageUrl);
                }
            }
        }


        /// if no product images set to the default
        if (DB::table('images')->where('entity_uuid', $product->uuid)->doesntExist()) {
            array_push($imageUrls, 'http://103.163.118.100/bkrm-api/storage/app/public/'
            . 'storage/product-images/product-default.png');

            DB::table('images')->insert([
                'uuid' => (string) Str::uuid(),
                'url' => 'http://103.163.118.100/bkrm-api/storage/app/public/'
                . 'storage/product-images/product-default.png',
                'store_id' => $store->id,
                'entity_uuid' => $product->uuid,
                'image_type' => 'product'
            ]);
        }

        if (isset($data['category_uuid'])) {
            $id = Category::where('uuid', $data['category_uuid'])->first()->id;
            unset($data['category_uuid']);
            $product->update(array_merge($data, ['category_id' => $id]));
        } else {
            $product->update($data);
        }

        return response()->json([
            'message' => "Product updated successfully",
            'data' => $product
        ], 200);
    }

    public function destroy(Store $store, Product $product)
    {
        $product->update(['status' => 'deleted']);
        return response()->json([
            'message' => 1,
            'data' => $product
        ], 200);
    }

    public function active(Store $store, Product $product)
    {
        $product->update(['status', 'active']);
        return response()->json([
            'message' => true,
            'data' => $product
        ], 200);
    }

    public function inactive(Store $store, Product $product)
    {
        $product->update(['status', 'inactive']);
        return response()->json([
            'message' => true,
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


    public function searchDefaultProduct(Request $request) {
        // $name = $request->query('name');
        // $barcode = $request->query('barcode');
        $searchKey = $request->query('searchKey');
        $limit = $request->query('limit');
        $page = $request->query('page');

        $data = [];
        $productInfos = [];
        $mergeImgPath = 'http://103.163.118.100/bkrm-api/storage/app/public/';

        
        if ($searchKey) {
            $productInfos = Barcode::
                where('bar_code', 'LIKE', '%' . $searchKey . '%')
                ->orWhere('product_name', 'LIKE', '%' . $searchKey . '%')
                ->offset($limit * ($page - 1))
                ->limit($limit)
                ->get()->toArray();
        }
        else {
            $productInfos = Barcode
                ::offset($limit*($page - 1))
                ->limit($limit)
                ->get()->toArray();
        }

        // if ($name === "" && $barcode === "") {
        //     $productInfos = Barcode::offset($limit * ($page - 1))->limit($limit)
        //         ->get()->toArray();
        // }

        foreach ($productInfos as $productInfo) {
            $mergeImgPath = 'http://103.163.118.100/bkrm-api/storage/app/public/';

            $img_url =  $mergeImgPath . $productInfo['image_url'];
            array_push($data, [
                'name' => $productInfo['product_name'],
                'img_url' => $img_url,
                'bar_code' => $productInfo['bar_code']
            ]);
        }

        return response()->json([
            'data' => $data,
        ], 200); 
    }
}
