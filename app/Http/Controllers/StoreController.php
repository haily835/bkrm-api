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
            'name' => 'required|string',
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


        $store = Store::create(array_merge($data, [
            'user_id' => auth()->user()->id,
            'uuid' => (string) Str::uuid()
        ]));

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
            'image' => 'nullable|image',
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
        return response()->json([
            'message' => 'Store updated successfully',
            'store' => $store,
        ], 200);
    }

    public function destroy(Store $store)
    {
        $isdeleted = Store::destroy($store->id);
        return response()->json([
            'message' => $isdeleted,
            'data' => $store
        ], 200);
    }

    public function activities(Request $request, Store $store)
    {
        $period = $request->query('period');
        $data = [];
        $purchaseOrders = $store->purchaseOrders()
            ->where('creation_date', '>', now()->subDays($period)->endOfDay())
            ->join('suppliers', 'purchase_orders.supplier_id', '=', 'suppliers.id')
            ->join('branches', 'purchase_orders.branch_id', '=', 'branches.id')
            ->select(
                'purchase_orders.purchase_order_code as code',
                'purchase_orders.total_amount as total_amount',
                'purchase_orders.creation_date as created_at',
                'purchase_orders.created_user_type as user_type',
                'purchase_orders.created_by as user_id',
                'suppliers.name as partner_name',
                'branches.name as branch_name'
            )
            ->get()->toArray();

        $purchaseOrders = array_map(function ($purchaseOrder) {
            return array_merge([
                'type' => 'purchase_order'
            ], $purchaseOrder);
        }, $purchaseOrders);

        $purchaseReturns = $store->purchaseReturns()
            ->where('creation_date', '>', now()->subDays($period)->endOfDay())
            ->join('suppliers', 'purchase_returns.supplier_id', '=', 'suppliers.id')
            ->join('branches', 'purchase_returns.branch_id', '=', 'branches.id')
            ->select(
                'purchase_returns.purchase_return_code as code',
                'purchase_returns.total_amount as total_amount',
                'purchase_returns.creation_date as created_at',
                'purchase_returns.created_user_type as user_type',
                'purchase_returns.created_by as user_id',
                'suppliers.name as partner_name',
                'branches.name as branch_name'
            )
            ->get()->toArray();

        $purchaseReturns = array_map(function ($purchaseReturn) {
            return array_merge([
                'type' => 'purchase_return'
            ], $purchaseReturn);
        }, $purchaseReturns);

        $orders = $store->orders()
            ->where('orders.created_at', '>', now()->subDays($period)->endOfDay())
            ->join('customers', 'orders.customer_id', '=', 'customers.id')
            ->join('branches', 'orders.branch_id', '=', 'branches.id')
            ->select(
                'orders.order_code as code',
                'orders.total_amount as total_amount',
                'orders.created_at as created_at',
                'orders.created_user_type as user_type',
                'orders.user_id as user_id',
                'customers.name as partner_name',
                'branches.name as branch_name'
            )
            ->get()->toArray();

        $orders = array_map(function ($order) {
            return array_merge([
                'type' => 'order'
            ], $order);
        }, $orders);

        $refunds = $store->refunds()
            ->where('refunds.created_at', '>', now()->subDays($period)->endOfDay())
            ->limit(20)
            ->join('customers', 'refunds.customer_id', '=', 'customers.id')
            ->join('branches', 'refunds.branch_id', '=', 'branches.id')
            ->select(
                'refunds.refund_code as code',
                'refunds.total_amount as total_amount',
                'refunds.created_at as created_at',
                'refunds.created_user_type as user_type',
                'refunds.created_by as user_id',
                'customers.name as partner_name',
                'branches.name as branch_name'
            )
            ->get()->toArray();

        $refunds = array_map(function ($refund) {
            return array_merge([
                'type' => 'refund'
            ], $refund);
        }, $refunds);

        $documents = array_merge($purchaseOrders, $purchaseReturns, $orders, $refunds);

        foreach ($documents as $document) {
            if ($document["user_type"] === 'owner') {
                $created_by = User::where('id', $document["user_id"])->first();
            } else {
                $created_by = Employee::where('id', $document["user_id"])->first();
            }

            if ($created_by) {
                array_push($data, array_merge($document, ['user_name' => $created_by->name]));
            }
        }

        usort($data, function ($a, $b) {
            $ad = new DateTime($a['created_at']);
            $bd = new DateTime($b['created_at']);

            if ($ad == $bd) {
                return 0;
            }

            return $ad > $bd ? -1 : 1;
        });
        return response()->json([
            'message' => 'get activity successfully',
            'data' => $data,
        ], 200);
    }

    // public function report(Request $request, Store $store) {
    //     $numOfProducts = $store->products()->where('status', '<>', 'deleted')->count();
    //     $numOfEmployees = $store->employees()->where('status', 'active')->count();
    //     $numOfCustomers = $store->customers()->where('status', 'active')->count();
    //     $numOfBranches = $store->branches()->where('status', 'active')->count();

    //     $start_date = $request->query('start_date') ? 
    //                 $request->query('start_date') . ' 00:00:00' 
    //                 : "";

    //     $end_date = $request->query('end_date') ?
    //                 $request->query('start_date') . ' 11:59:59'
    //                 : "";

    //     $purchaseOrders = $store->purchaseOrders()
    //         ->where('creation_date', '>=', $start_date)
    //         ->where('creation_date', '<', $end_date)->get();

    //     $orders = $store->orders()
    //         ->where('creation_date', '<', $end_date)->get();


    //     $purchaseReturns = $store->purchaseReturns()->where('creation_date', '>', now()->subDays($period)->endOfDay());

    //     $refunds = $store->refunds()->where('created_at', '>', now()->subDays($period)->endOfDay());

    //     $outAccount = $purchaseOrders->sum('total_amount') + $refunds->sum('total_amount');
    //     $inAccount = $orders->sum('total_amount') + $purchaseReturns->sum('total_amount');

    //     // sales per product
    //     $products = $store->products;

    //     $productData = [];
    //     foreach($products as $product) {
    //         $result = $product->orderDetails()
    //             ->where('created_at', '>', now()->subDays($period)->endOfDay())
    //             ->select(DB::raw('sum(quantity * unit_price) as result' ))->first();
    //         array_push($productData, ['product_name' => $product->name, 'totalSales' => $result['result']]);
    //     }

    //     // customer total sales
    //     // $customer_sales = $orders->groupBy('customer_id')->count();

    //     $customer_sales = 
    //             DB::table('orders')->where('orders.store_id', $store->id)
    //             ->join('customers', 'orders.customer_id', '=', 'customers.id')
    //             ->selectRaw('count(orders.id) as number_of_orders, sum(total_amount) as total_sale, customers.name, customers.id')
    //             ->groupBy('customer_id')
    //             ->get();

    //     $employee_sales = 
    //             DB::table('orders')
    //             ->where('orders.store_id', $store->id)
    //             ->where('orders.created_user_type', 'employee')
    //             ->join('employees', 'orders.user_id', '=', 'employees.id')
    //             ->selectRaw('count(orders.id) as number_of_orders, sum(total_amount) as total_sale, employees.name, employees.id')
    //             ->groupBy('user_id')
    //             ->get();

    //     return response()->json([
    //         'message' => 'get report successfully',
    //         'data' => [
    //             'product_report' => $productData,
    //             'inAccount' => $inAccount,
    //             'outAccount' => $outAccount,
    //             'numOfProducts' => $numOfProducts,
    //             'numOfEmployees' => $numOfEmployees,
    //             'numOfBranches' => $numOfBranches,
    //             'numOfCustomers' => $numOfCustomers,
    //             'customerSales' => $customer_sales,
    //             'employeeSales' => $employee_sales,
    //         ]
    //     ], 200);
    // }

    // private function getBestSellingCategory($store_id, $begin, $end, $order_by)
    // {
    //     $item_list = DB::table('orders')->where('orders.branch_id', $branch_id)
    //         ->join('order_details', 'order_details.order_id', '=', 'orders.id')
    //         ->join('products', 'products.id', '=', 'order_details.product_id')
    //         ->join('categories', 'categories.id', '=', 'products.category_id')
    //         ->selectRaw('categories.id, categories.name, SUM(order_details.unit_price*order_details.quantity) AS total_sell_price, SUM(order_details.quantity) AS total_quantity')
    //         ->groupByRaw('categories.id, categories.name')
    //         ->orderByRaw("categories.id DESC");

    //     if ($begin) {
    //         $item_list = $item_list->where('orders.created_at', '>=', $begin);
    //     }
    //     if ($end) {
    //         $item_list = $item_list->where('orders.created_at', '<', $end);
    //     }
    //     return $item_list->get();
    // }

}
