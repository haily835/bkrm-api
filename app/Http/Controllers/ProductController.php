<?php

namespace App\Http\Controllers;

use App\Models\BranchInventory;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Store;
use App\Models\Category;
use App\Models\Branch;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use App\Models\Barcode;
use App\Models\InventoryCheckDetail;
use Illuminate\Support\Facades\Validator;

class ProductController extends Controller
{
    public function indexOfBranch(Request $request, Store $store, Branch $branch)
    {
        $search_key = $request->query("searchKey");
        $limit = $request->query("limit");
        $page = $request->query("page");

        // extract query string
        $min_standard_price = $request->query('minStandardPrice');
        $max_standard_price = $request->query('maxStandardPrice');
        $min_list_price = $request->query('minListPrice');
        $max_list_price = $request->query('maxListPrice');
        $min_inventory = $request->query('minInventory');
        $max_inventory = $request->query('maxInventory');
        $categoryId =  $request->query('categoryId');
        $status = $request->query('status');
        $order_by = $request->query('orderBy');
        $sort = $request->query('sort');

        // set up query
        $queries = [];

        if ($min_standard_price) {
            array_push($queries, ['products.standard_price', '>=', $min_standard_price]);
        }

        if ($max_standard_price) {
            array_push($queries, ['products.standard_price', '<=', $max_standard_price]);
        }

        if ($min_list_price) {
            array_push($queries, ['products.list_price', '>=', $min_list_price]);
        }

        if ($max_list_price) {
            array_push($queries, ['products.list_price', '<=', $max_list_price]);
        }

        if ($min_inventory) {
            array_push($queries, ['products.quantity_available', '>=', $min_inventory]);
        }

        if ($max_inventory) {
            array_push($queries, ['products.quantity_available', '<=', $max_inventory]);
        }

        if ($status) {
            array_push($queries, ['products.status', '=', $status]);
        } else {
            array_push($queries, ['products.status', '<>', 'deleted']);
        }

        if ($categoryId) {
            array_push($queries, ['products.category_id', '=', $categoryId]);
        }


        $products = [];
        $db_query = $store->products()
            ->where($queries)
            ->whereNull('parent_product_code');

        if ($search_key) {
            $db_query = $db_query
                ->where(function ($query) use ($search_key) {
                    $query->where('products.name', 'LIKE', '%' . $search_key . '%')
                        ->orWhere('products.bar_code', 'LIKE',  '%' . $search_key . '%')
                        ->orWhere('products.product_code', 'LIKE',  '%' . $search_key . '%');
                });
        }

        $total_row = $db_query->count();

        if ($limit) {
            $products = $db_query
                ->offset(($page) * $limit)
                ->orderBy($order_by, $sort)
                ->limit($limit)
                ->get()
                ->toArray();
        } else {
            $products = $db_query
                ->orderBy($order_by, $sort)
                ->get()
                ->toArray();
        }
        $data = [];

        foreach ($products as $product) {
            // $firstImageUrl = DB::table('images')->where('entity_uuid', $product['uuid'])->first();
            $category = $store->categories->where('id', $product['category_id'])->first();
            unset($product['category_id']);

            // get branch inventory of that product
            $branch_product = $branch->inventory()->where('product_id', $product['id'])->first();

            if ($branch_product) {
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

            $batches = DB::table('product_batches')
                ->where('store_id', $store->id)
                ->where('branch_id', $branch->id)
                ->where('product_id', $product['id'])
                ->orderBy('expiry_date', 'desc')
                ->get();

            array_push($data, array_merge($product, [
                'category' => $category,
                'batches' => $batches,
                'product_prices' => DB::table('product_prices')->where('product_id', $product['id'])->get(),
                'inventory_checks' => InventoryCheckDetail::where('product_id', $product['id'])->get(),
                'branch_inventories' => BranchInventory::where('product_id', $product['id'])->join('branches', 'branches.id', 'branch_inventories.branch_id')->where('branches.status', 'active')->get(),
                'branch_quantity' => $branch_quantity,
            ]));
        }

        return response()->json([
            'data' => $data,
            'total_rows' => $total_row
        ], 200);
    }

    public function searchBranchInventory(Request $request, Store $store, Branch $branch)
    {
        $search_key = $request->query("searchKey");

        $products = [];

        if ($search_key) {
            $products = $store->products()
                ->where('products.status', '=', 'active')
                ->where('products.has_variance', false)
                ->where(function ($query) use ($search_key) {
                    $query->where('products.name', 'LIKE', '%' . $search_key . '%')
                        ->orWhere('products.bar_code', 'LIKE',  '%' . $search_key . '%')
                        ->orWhere('products.product_code', 'LIKE',  '%' . $search_key . '%');
                })
                ->get()
                ->toArray();
        } else {
            $products = $store->products()
            ->where('products.status', '=', 'active')
            ->where('products.has_variance', false)
            ->get()->toArray();
        }

        $data = [];

        foreach ($products as $product) {
            $category = $store->categories->where('id', $product['category_id'])->first();
            unset($product['category_id']);

            // get branch inventory of that product
            $branch_product = $branch->inventory()->where('product_id', $product['id'])->first();
            $branch_quantity = $branch_product->quantity_available;
            $batches = DB::table('product_batches')
                ->where('store_id', $store->id)
                ->where('branch_id', $branch->id)
                ->orderBy('expiry_date', 'desc')
                ->where('product_id', $product['id'])->get();

            array_push($data, array_merge($product, [
                'category' => $category,
                'branch_quantity' => $branch_quantity,
                'batches' => $batches,
                'branch_inventories' => BranchInventory::where('product_id', $product['id'])->join('branches', 'branches.id', 'branch_inventories.branch_id')->where('branches.status', 'active')->get(),
            ]));
        }

        return response()->json([
            'data' => $data,
        ], 200);
    }

    public function createBatch(Request $request, Store $store, Branch $branch)
    {
        $validated = $request->validate([
            'product_uuid' => 'required|string',
            'batch_code' => 'nullable|string',
            'expiry_date' => 'nullable|date_format:Y-m-d',
            'quantity' => 'required|numeric'
        ]);


        $product = $store->products()->where('uuid', $validated['product_uuid'])->first();
        $batch_code = $validated['batch_code'];

        if (!$validated['batch_code']) {
            $last_id = DB::table('product_batches')
                ->where('store_id', $store->id)
                ->where('branch_id', $branch->id)
                ->where('product_id', $product->id)
                ->get()->count();
            $batch_code = 'L' . sprintf('%04d', $last_id + 1);
        }

        DB::table('product_batches')->insert([
            'store_id' => $store->id,
            'branch_id' => $branch->id,
            'product_id' => $product->id,
            'expiry_date' => $validated['expiry_date'],
            'quantity' => $validated['quantity'],
            'batch_code' => $batch_code
        ]);
        return response()->json(['message' => 'batch created']);
    }

    public function store(Request $request, Store $store)
    {
        $product = $request->all();
        $imageUrls = array_key_exists('img_url', $product) ? ($product['img_url'] ? $product['img_url'] : []) : [];
        $images = array_key_exists('images', $product) ? ($product['images'] ? $product['images'] : []) : [];
        $branch_uuid = $product['branch_uuid'];
        $category_uuid = $product['category_uuid'];
        $category_id = $store->categories()->where('uuid', $category_uuid)->first()->id;
        unset($product['img_url']);
        unset($product['images']);
        unset($product['branch_uuid']);
        unset($product['category_uuid']);
        $product = array_merge($product, ['category_id' => $category_id]);

        $branch_id = Branch::where('uuid', $branch_uuid)->first()->id;
        $newProduct = $this->createUpdateProduct($product, $store->id, $branch_id, $images, $imageUrls, null);
        return response()->json(['data' => $newProduct]);
    }
    
    public function addProductWithVariation(Request $request, Store $store)
    {
        /////////// validation ////////////
        $product = $request->validate([
            'variations' => 'required|array',
            'category_uuid' => 'required|string',
            'name' => 'required|string',
            'list_price' => ['numeric', 'required'],
            'standard_price' => ['numeric', 'required'],
            'quantity_per_unit' => ['string', 'nullable'],
            'min_reorder_quantity' => ['numeric', 'nullable'],
            'images' => 'nullable|array',
            'img_url' => 'nullable|string',
            'description' => 'nullable|string',
            'attribute_value' => 'nullable|string',
            'has_batches' => 'nullable|string',
            'notification_period' => 'nullable|string',
            'max_order' => 'nullable|numeric',
            'branch_uuid' => 'nullable|string',
            'quantity' => 'nullable|numeric',
        ]);

        $imageUrls = array_key_exists('img_url', $product) ? ($product['img_url'] ? [$product['img_url']] : []) : [];
        $images = array_key_exists('images', $product) ? ($product['images'] ? $product['images'] : []) : [];
        $branch_uuid = $product['branch_uuid'];
        $category_uuid = $product['category_uuid'];
        $category_id = $store->categories()->where('uuid', $category_uuid)->first()->id;
        $variations = $product['variations'];

        unset($product['img_url']);
        unset($product['images']);
        unset($product['branch_uuid']);
        unset($product['category_uuid']);
        unset($product['variations']);

        $product = array_merge($product, ['category_id' => $category_id, 'has_variance' => true]);
        $branch_id = Branch::where('uuid', $branch_uuid)->first()->id;
        
        $parentProduct = $this->createUpdateProduct($product, $store->id, $branch_id, $images, $imageUrls, null);
        foreach ($variations as $key => $variation) {
            $childProduct = json_decode($variation, true);
            $this->createUpdateProduct(
                array_merge($childProduct, [
                    'name' => $parentProduct['name'] . '-' . $childProduct['name'],
                    'description' => $parentProduct['description'],
                    'product_code' => $parentProduct['product_code'] . '-' . ($key+1),
                    'quantity_per_unit' => $parentProduct['quantity_per_unit'],
                    'category_id' => $category_id,
                    'min_reorder_quantity' => $product['min_reorder_quantity'],
                    'max_order' => $parentProduct['max_order'],
                    'has_batches' => $parentProduct['has_batches'],
                    'has_variance' => false,
                    'on_sale' => false,
                    'parent_product_code' => $parentProduct['product_code'],
                    'notification_period' => $parentProduct['notification_period'],
                ]),
                $store->id, $branch_id, $images, $imageUrls, null, true);
        }
        return response()->json([
            'message' => "Product created successfully"
        ], 200);
    }

    public function show(Request $request, Store $store, Product $product)
    {
        $category = $product->category;
        $images = DB::table('images')->where('entity_uuid', $product['uuid'])->get('url');
        $suppliers = $product->suppliers->get('name');

        $variations = $store
            ->products()
            ->where('parent_product_code', $product->product_code)
            ->where('products.status', '=', 'active')
            ->get()->toArray();

        $variationsData = [];
        // get branch inventory of that product
        $branch_uuid = $request->query('branch_uuid');
        $branch_id = Branch::where('uuid', $branch_uuid)->first()->id;
        foreach ($variations as $variation) {
            // $firstImageUrl = DB::table('images')->where('entity_uuid', $variation['uuid'])->first();
            $category = $store->categories->where('id', $variation['category_id'])->first();
            unset($product['category_id']);


            $branch_product = BranchInventory::where('branch_id', $branch_id)->where('product_id', $variation['id'])->first();

            array_push($variationsData, array_merge($variation, [
                // 'img_url' => $firstImageUrl ? $firstImageUrl->url : "",
                'category' => $category,
                'branch_quantity' => $branch_product->quantity_available,
            ]));
        }


        $data = array_merge($product->toArray(), [
            'category' => $category,
            'branch_inventories' => BranchInventory::where('product_id', $product['id'])
                ->join('branches', 'branches.id', 'branch_inventories.branch_id')
                ->where('branches.status', 'active')
                ->get(),
            'batches' => DB::table('product_batches')
                ->where('store_id', $store->id)
                ->where('branch_id', $branch_id)
                ->where('product_id', $product['id'])->get(),
            'images' => $images,
            'suppliers' => $suppliers,
            'variations' => $variationsData,
        ]);

        return response()->json([
            'data' => $data,
        ], 200);
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
            'img_urls' => 'nullable|string',
            'images' => 'nullable',
            'has_batches' => 'nullable|numeric',
            'notification_period' => 'nullable|numeric',
        ]);


        DB::table('images')->where('entity_uuid', $product['uuid'])->delete();
        $imageUrls = [];
        if (array_key_exists('img_urls', $data)) {
            $img_url_array = explode(',', $data['img_urls']);
            foreach ($img_url_array as $img_url) {
                if ($img_url) {
                    array_push($imageUrls, $img_url);
                }
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

                    // DB::table('images')->insert([
                    //     'uuid' => (string) Str::uuid(),
                    //     'url' => $imageUrl,
                    //     'store_id' => $store->id,
                    //     'entity_uuid' => $product['uuid'],
                    //     'image_type' => 'product'
                    // ]);

                    array_push($imageUrls, $imageUrl);
                }
            }
        }


        /// if no product images set to the default
        // if (DB::table('images')->where('entity_uuid', $product->uuid)->count() === 0) {
        //     array_push($imageUrls, 'http://103.163.118.100/bkrm-api/storage/app/public/product-images/product-default.png');

        //     DB::table('images')->insert([
        //         'uuid' => (string) Str::uuid(),
        //         'url' => 'http://103.163.118.100/bkrm-api/storage/app/public/product-images/product-default.png',
        //         'store_id' => $store->id,
        //         'entity_uuid' => $product->uuid,
        //         'image_type' => 'product'
        //     ]);
        // }

        if ($data['list_price'] != $product['list_price']) {
            DB::table('product_prices')->insert([
                'product_id' => $product['id'],
                'price' => $data['list_price'],
                'store_id' => $store->id
            ]);
        }

        unset($data['img_urls']);
        unset($data['images']);

        if (isset($data['category_uuid'])) {
            $id = Category::where('uuid', $data['category_uuid'])->first()->id;
            unset($data['category_uuid']);
            $product->update(array_merge($data, ['category_id' => $id, 'img_urls' => json_encode($imageUrls)]));
        } else {
            $product->update(array_merge($data, ['img_urls' => json_encode($imageUrls)]));
        }

        return response()->json([
            'message' => "Product updated successfully",
            'data' => $product,
            'img_urls' => $imageUrls,
        ], 200);
    }

    public function destroy(Store $store, Product $product)
    {
        $product->update(['status' => 'deleted']);
        return response()->json([
            'message' => 1,
            'data' => $product,

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

    public function searchDefaultProduct(Request $request)
    {
        // $name = $request->query('name');
        // $barcode = $request->query('barcode');
        $searchKey = $request->query('searchKey');
        $limit = $request->query('limit');
        $page = $request->query('page');

        $data = [];
        $productInfos = [];
        $mergeImgPath = 'https://www.cuahangcuatoi.net/bkrm-api/storage/app/public/';

        if ($searchKey) {
            $productInfos = Barcode::where('bar_code', 'LIKE', '%' . $searchKey . '%')
                ->orWhere('product_name', 'LIKE', '%' . $searchKey . '%')
                ->offset($limit * ($page - 1))
                ->limit($limit)
                ->get()->toArray();
        } else {
            $productInfos = Barcode::offset($limit * ($page - 1))
                ->limit($limit)
                ->get()->toArray();
        }

        // if ($name === "" && $barcode === "") {
        //     $productInfos = Barcode::offset($limit * ($page - 1))->limit($limit)
        //         ->get()->toArray();
        // }

        foreach ($productInfos as $productInfo) {
            $mergeImgPath = 'https://www.cuahangcuatoi.net/bkrm-api/storage/app/public/';

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


    public function addProductByJson(Request $request, Store $store, Branch $branch)
    {
        $products = $request->input('json_data');
        $messages = [];
        foreach ($products as $key => $product) {
            $validatedResult = $this->validateNewProduct($product, $store->id);
            if ($validatedResult) {
                array_push(
                    $messages, 
                    array_merge($this->validateNewProduct($product, $store->id), ['row' => $key])
                );
            }
        }

        if (count($messages)) {
            return response()->json([
                'status' => 0,
                'data'=> $messages,
            ]);
        }

        foreach ($products as $key => $product) {
            $category_id = "";
            $category = $store->categories()->where('name', '=', $product['category_name'])->first();
            if ($category) {
                $category_id = $category->id;
            } else {
                if($product['category_name']) {
                    $newCategory = Category::create([
                        'uuid' => (string) Str::uuid(),
                        'name' => $product['category_name'],
                        'store_id' => $store->id
                    ]);
                    $category_id = $newCategory->id;
                } 
            };

            unset($product['category_name']);
            $product = array_merge($product, ['category_id' => $category_id]);
            $image_urls = [];
            if (array_key_exists('img_urls', $product)) {
                $img_url_array = explode(',', $product['img_urls']);
                foreach ($img_url_array as $img_url) {
                    if ($img_url) {
                        array_push($image_urls, $img_url);
                    }
                }
            }
            
            unset($product['img_urls']);
            array_push(
                $messages, 
                $this->createUpdateProduct($product, $store->id, $branch->id, [], $image_urls ? $image_urls : [], null)
            );
        }

        return response()->json([
            'status' => 1,
            'data'=>$messages,
        ]);
    }

    public function productOrderRecommend(Request $request, Store $store, Branch $branch)
    {
        // get all product that out of stoke
        // for each product get the lastest 10 purchase order details of it in branch
        $out_of_stock_products = $branch->inventory()
            ->join('products', 'branch_inventories.product_id', '=', 'products.id')
            ->where('branch_inventories.quantity_available', '<=', 'products.min_reorder_quantity')
            ->where('products.status', '=', 'active')
            ->where('products.has_variance', '=', false)
            ->get()->toArray();


        $data = [];
        foreach ($out_of_stock_products as $product) {
            $purchase_histories = DB::table('purchase_order_details')
                ->where('purchase_order_details.branch_id', $branch->id)
                ->where('product_id', '=', $product['product_id'])
                ->join('purchase_orders', 'purchase_orders.id', '=', 'purchase_order_details.purchase_order_id')
                ->join('suppliers', 'suppliers.id', '=', 'purchase_orders.supplier_id')
                ->orderBy('purchase_order_details.created_at', 'desc')
                ->select(
                    'suppliers.name as name',
                    'suppliers.id as supplier_id',
                    'suppliers.phone as phone',
                    'suppliers.uuid as supplier_uuid',
                    'purchase_order_details.quantity as quantity',
                    'purchase_orders.purchase_order_code as purchase_order_code'
                )
                ->limit(10)
                ->get()->toArray();
            array_push($data, array_merge($product, [
                'purchase_histories' => $purchase_histories,
                'branch_inventories' => BranchInventory::where('product_id', $product['product_id'])->join('branches', 'branches.id', 'branch_inventories.branch_id')->where('branches.status', 'active')->get()
            ]));
        }

        return response()->json(['data' => $data], 200);
    }
    

    private function validateNewProduct($product, $store_id) {
        $typeValidator = Validator::make($product, [
            'name' => ['required', 'string'],
            'list_price' => ['required', 'numeric'],
            'standard_price' => ['required', 'numeric'],
            'category_name' => ['required', 'string'],
            'bar_code' => ['nullable', 'string'],
            'quantity_per_unit' => ['nullable', 'string'],
            'min_reorder_quantity' => ['nullable', 'numeric'],
            'max_order' => 'nullable|numeric',
            'description' => 'string|nullable',
            'has_batches' => 'nullable|numeric',
            'notification_period' => 'nullable|numeric',
            'quantity' => "nullable|numeric",
        ], [
            'required' => 'Bị thiếu',
            'string' => 'Kiểu chuỗi',
            'numeric' => 'Kiểu số',
            'array' => 'Kiểu chuỗi phân cách bởi dấu ,'
        ]);

        $errors = [];
        if ($typeValidator->fails()) {
            $errors = array_merge($errors, $typeValidator->errors()->toArray());
        }

        if (array_key_exists('bar_code', $product)) {
            if ($product['bar_code']) {
                if (Product::where('store_id', $store_id)->where('bar_code', $product['bar_code'])->first()) {
                    $errors = array_merge($errors, [
                        "bar_code" => "Mã barcode đã được sử dụng"
                     ]);
                }
            }
        }

        if (count($errors)) {
            return $errors;
        }
    }

    private function createUpdateProduct($product, $store_id, $branch_id, $images, $image_urls, $product_id, $isVariation = false) {
        $typeValidator = Validator::make($product, [
            'name' => [$product_id ? 'nullable' : 'required', 'string'],
            'list_price' => [$product_id ? 'nullable' : 'required', 'numeric'],
            'standard_price' => [$product_id ? 'nullable' : 'required', 'numeric'],
            'category_id' => [$product_id ? 'nullable' : 'required', 'numeric'],
            'bar_code' => ['nullable', 'string'],
            'quantity_per_unit' => ['nullable', 'string'],
            'min_reorder_quantity' => ['nullable', 'numeric'],
            'max_order' => 'nullable|numeric',
            'description' => 'string|nullable',
            'has_batches' => 'nullable|numeric',
            'notification_period' => 'nullable|numeric',
            'quantity' => "nullable|numeric",
        ], [
            'required' => 'Bị thiếu',
            'string' => 'Kiểu chuỗi',
            'numeric' => 'Kiểu số',
            'array' => 'Kiểu chuỗi phân cách bởi dấu ,'
        ]);

        $errors = [];
        if ($typeValidator->fails()) {
            $errors = array_merge($errors, $typeValidator->errors()->toArray());
        }

        if (array_key_exists('bar_code', $product)) {
            if ($product['bar_code']) {
                if (Product::where('store_id', $store_id)->where('bar_code', $product['bar_code'])->first()) {
                    $errors = array_merge($errors, [
                        "bar-code" => "Mã barcode đã được sử dụng"
                     ]);
                }
            }
        }

        if (count($errors)) {
            return $errors;
        }

        $imageUrls = $image_urls;
        foreach ($images as $image) {
            $imagePath = $image->store('product-images', 'public');

            $sized_image = Image::make(public_path("storage/{$imagePath}"))->fit(1000, 1000);
            $sized_image->save();
            $imageUrl = 'https://www.cuahangcuatoi.net/bkrm-api/storage/app/public/'
                . $imagePath;
            array_push($imageUrls, $imageUrl);
        }

        if ($product_id) {
            $updatedProduct = Product::where('id', $product_id)
                ->update(array_merge($product, ['img_urls' => json_encode($imageUrls)]));
            return $updatedProduct;
        }

        $quantity = 0;
        if (array_key_exists('quantity', $product)) {
            $quantity = $product['quantity'];
            unset($product['quantity']);
        }
        $last_id = Product::where('store_id', $store_id)->whereNull('parent_product_code')->count();
        $product_code = $isVariation ? $product['product_code'] : 'SP' . sprintf('%05d', $last_id + 1);
        $newProduct =  Product::create(array_merge($product, [
            'store_id' => $store_id,
            'uuid' => (string) Str::uuid(),
            'product_code' => $product_code,
            'img_urls' =>  json_encode($imageUrls),
            'quantity_available' => $quantity,
        ]));

        $branches = Branch::where('store_id', $store_id)->where('status', 'active')->get();
        foreach ($branches as $branch) {
            if ($branch->id === $branch_id) {
                BranchInventory::create([
                    'store_id' => $store_id,
                    'branch_id' => $branch->id,
                    'product_id' => $newProduct->id,
                    'quantity_available' => $quantity,
                ]);

                if ($product['has_batches']) {
                    DB::table('product_batches')->insert([
                        'store_id' => $store_id,
                        'branch_id' => $branch_id,
                        'product_id' => $newProduct->id,
                        'quantity' => $quantity,
                        'batch_code' => 'L0001'
                    ]);
                }
            } else {
                BranchInventory::create([
                    'store_id' => $store_id,
                    'branch_id' => $branch_id,
                    'product_id' => $newProduct->id,
                    'quantity_available' => 0,
                ]);
            }
        }
        return $newProduct;
    }
}
