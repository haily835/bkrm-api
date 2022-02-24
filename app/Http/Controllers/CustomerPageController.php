<?php

namespace App\Http\Controllers;

use App\Models\Store;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use DateTime;

class CustomerPageController extends Controller
{
    public function storeInfo(Request $request)
    {
        $store_web_page = $request->query('store_web_page');
        $store = Store::where('web_page', $store_web_page)->first();
        return response()->json(['data'=>$store]);
    }

    public function storeProducts(Request $request, Store $store) {
        $limit = $request->query("limit") ? $request->query("limit") : 10;
        $page = $request->query("page") ? $request->query("page") : 1;

        // $productQuery = $store->products()->where('status', 'active');
        $total_row = $store->products()->where([
            ['status', '<>', 'inactive'],
            ['status', '<>', 'deleted'],
        ])->count();
        
        $products = $store->products()
            ->where([
                
                ['status', '<>', 'inactive'],
                ['status', '<>', 'deleted'],
            ])
            ->orderBy('created_at', 'desc')
            ->offset(($page - 1) * $limit)
            ->limit($limit)
            ->get()->toArray();

            $data = [];

        foreach ($products as $product) {
            $firstImageUrl = DB::table('images')->where('entity_uuid', $product['uuid'])->get('url');
            $category = $store->categories->where('id', $product['category_id'])->first();
            unset($product['category_id']);

            array_push($data, array_merge($product, [
                'img_urls' => $firstImageUrl ? $firstImageUrl : "http://103.163.118.100/bkrm-api/storage/app/public/product-images/product-default.png",
                'category' => $category,
            ]));
        }

        return response()->json([
            'data' => $data,
            'total_rows' => $total_row
        ], 200);
    }
}
