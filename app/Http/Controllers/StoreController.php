<?php

namespace App\Http\Controllers;

use App\Mail\StoreEmail;
use App\Models\Store;
use App\Models\Employee;
use App\Models\User;
use App\Models\Branch;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Config;
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
            'email_configuration' => 'nullable|string',
            'web_configuration' => 'nullable|string',
            'general_configuration' => 'nullable|string',
            'images' => 'nullable'
        ]);
        return response()->json([
            'message' => 'Store updated successfully',

            'data' => $data,
        ], 200);
        if (array_key_exists('web_configuration', $data)) {
            if ($data['web_configuration']) {
                $config = json_decode($data['web_configuration'], true);
                $web_page = $config['webAddress'];
                $store->update(['web_page' => $web_page]);
            }
        }
        $banners = [];
        if (array_key_exists('images', $data)) {
            if ($data['images'] != null) {
                foreach ($data['images'] as $image) {
                    $imagePath = $image->store('store-images', 'public');
                    $imageUrl = 'http://103.163.118.100/bkrm-api/storage/app/public/'
                        . $imagePath;
                    array_push($banners, $imageUrl);
                }
            }
            unset($data['images']);
            $data = array_merge($data, ['banners' => $banners]);
        }
        $store->update($data);
        return response()->json([
            'message' => 'Store updated successfully',
            'store' => $store,
            'data' => $banners,
        ], 200);
    }

    public function updateStoreConfiguration(Request $request, Store $store)
    {
        $data = $request->validate([
            'facebook' => 'nullable|string',
            'instagram' => 'nullable|string',
            'image' => 'nullable',
            'custom_web' => 'nullable|string',
        ]);

        $imagePath = "";
        if (array_key_exists('image', $data)) {
            if ($data['image'] != "") {
                $imagePath = $data['image']->store('store-images', 'public');
                $sized_image = Image::make(public_path("storage/{$imagePath}"))->fit(1000, 1000);
                $sized_image->save();
                $imagePath = 'http://103.163.118.100/bkrm-api/storage/app/public/' . $imagePath;
            }
        }

        unset($data['image']);
        $store_configuration = array_merge($data, ['img_url' => $imagePath]);
        $store->update(['store_configuration' => json_encode($store_configuration)]);
        return response()->json([
            'message' => 'Store updated successfully',
            'store' => $store,
        ], 200);
    }

    public function show(Store $store)
    {
        return response()->json([
            'data' => $store
        ], 200);
    }

    public function activities(Request $request, Store $store, Branch $branch)
    {
        $period = $request->query('period');
        $data = [];
        $purchaseOrders = $branch->purchaseOrders()
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

        $purchaseReturns = $branch->purchaseReturns()
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

        $orders = $branch->orders()
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

        $refunds = $branch->refunds()
            ->where('refunds.created_at', '>', now()->subDays($period)->endOfDay())
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

        $inventoryChecks = $branch->inventoryChecks()
            ->where('inventory_checks.created_at', '>', now()->subDays($period)->endOfDay())
            ->select(
                'inventory_checks.inventory_check_code as code',
                'inventory_checks.total_amount as total_amount',
                'inventory_checks.created_at as created_at',
                'inventory_checks.created_user_type as user_type',
                'inventory_checks.created_by as user_id',
            )
            ->get()->toArray();

        $inventoryChecks = array_map(function ($inventoryCheck) {
            return array_merge([
                'type' => 'inventory_check'
            ], $inventoryCheck);
        }, $inventoryChecks);

        $documents = array_merge($purchaseOrders, $purchaseReturns, $orders, $refunds, $inventoryChecks);

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

    public function sendEmail(Request $request, Store $store)
    {
        $email_configuration = json_decode($store->email_configuration, true);
        if (!is_null($email_configuration)) {
            $config = array(
                'driver'     =>     'smtp',
                'host'       =>     'smtp.gmail.com',
                'port'       =>     587,
                'username'   =>     $email_configuration['username'],
                'password'   =>     $email_configuration['password'],
                'encryption' =>     'tls',
                'from'       =>     array('address' => $email_configuration['username'], 'name' => $store->name)
            );
            Config::set('mail', $config);
        }
        $validated = $request->validate([
            'subject' => 'required|string',
            'email' => 'required|string',
            'name' => 'required|string',
            'content' => 'required|string'
        ]);
        Mail::send(new StoreEmail($validated));
    }
}
