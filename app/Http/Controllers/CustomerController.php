<?php

namespace App\Http\Controllers;

use App\Models\Store;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Facades\Validator;

class CustomerController extends Controller
{
    public function index(Request $request, Store $store)
    {
        $search_key = $request->query('searchKey');
        $limit = $request->query('limit');
        $page = $request->query('page');

        $db_query = $store->customers()
            ->where('status', '<>', 'deleted')
            ->where('type', '<>', 'default')
            ->orderBy('created_at', 'desc');

        if ($search_key) {
            $db_query = $db_query->where(function ($query) use ($search_key) {
                $query->where('name', 'like', '%' . $search_key . '%')
                    ->orWhere('phone', 'like', '%' . $search_key . '%')
                    ->orWhere('customer_code', 'like', '%' . $search_key . '%')
                    ->orWhere('email', 'like', '%' . $search_key . '%');
            });
        }
        $total_rows = $db_query->count();
        if ($limit) {
            $customers = $db_query->offset($limit * $page)->limit($limit)->get();
        } else {
            $customers = $db_query->get();
        }
        $data = [];
        foreach ($customers as $customer) {
            $total_payment = $store->orders()->where('customer_id', '=', $customer->id)->sum('total_amount');
            $total_paid = $store->orders()->where('customer_id', '=', $customer->id)->sum('paid_amount');
            $total_discount = $store->orders()->where('customer_id', '=', $customer->id)->sum('discount');
            $debt = $total_payment - $total_paid - $total_discount;

            array_push($data, array_merge($customer->toArray(), ['total_payment' => $total_payment, 'debt' => $debt]));
        }
        return response()->json([
            'data' => $data,
            'total_rows' => $total_rows
        ], 200);
    }

    public function store(Request $request, Store $store)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'email' => 'nullable|string',
            'phone' => 'nullable|string',
            'address' => 'nullable|string',
            'ward' => 'nullable|string',
            'district' => 'nullable|string',
            'province' => 'nullable|string',
            'payment_info' => 'nullable|string',
            'image' => 'nullable'
        ]);

        if (array_key_exists('phone', $validated)) {
            if (count($store->customers()->where('phone', $validated['phone'])->get())) {
                return response()->json(['message' => 'Customer phone number existed'], 400);
            }
        }

        $imagePath = "";
        if (array_key_exists('image', $validated)) {
            if ($validated['image'] != null) {
                $imagePath = $validated['image']->store('customer-images', 'public');
                $sized_image = Image::make(public_path("storage/{$imagePath}"))->fit(1000, 1000);
                $sized_image->save();
            }
        } else {
            $imagePath = 'http://103.163.118.100/bkrm-api/storage/app/public/customer-images/customer-default.png';
        }

        $last_id = count($store->customers);
        $customer_code = 'KH' . sprintf('%06d', $last_id + 1);

        $customer = Customer::create(array_merge(
            [
                'store_id' => $store->id,
                'uuid' => (string) Str::uuid(),
                'img_url' => $imagePath,
                'customer_code' => $customer_code
            ],
            $validated
        ));

        return response()->json([
            'data' => $customer
        ], 200);
    }

    public function show(Store $store, Customer $customer)
    {
        $total_payment = $store->orders()->where('customer_id', '=', $customer->id)->sum('total_amount');
        $total_paid = $store->orders()->where('customer_id', '=', $customer->id)->sum('paid_amount');
        $total_discount = $store->orders()->where('customer_id', '=', $customer->id)->sum('discount');
        $debt = $total_payment - $total_paid - $total_discount;

        return response()->json([
            'data' => array_merge($customer->toArray(), ['total_payment' => $total_payment, 'debt' => $debt])

        ], 200);
    }


    public function update(Request $request, Store $store, Customer $customer)
    {
        $validated = $request->validate([
            'name' => 'nullable|string',
            'email' => 'nullable|string',
            'phone' => 'nullable|string',
            'address' => 'nullable|string',
            'ward' => 'nullable|string',
            'city' => 'nullable|string',
            'province' => 'nullable|string',
            'payment_info' => 'nullable|string',
            'status' => 'nullable|string',
        ]);

        if (array_key_exists('phone', $validated)) {
            $existedCustomer = $store->customers()->where('id', '<>', $customer->id)->where('phone', $validated['phone'])->first();
            if ($existedCustomer) {
                return response()->json(['message' => 'Can not update'], 400);
            }
        }

        $customer->update($validated);

        return response()->json([
            'data' => $customer
        ], 200);
    }

    public function destroy(Store $store, Customer $customer)
    {
        $numOfCust = $store->customers()->where('status', 'active')->count();
        if ($numOfCust <= 1) {
            return response()->json([
                'message' => 'Can not delete last customer',
                'data' => $customer
            ], 404);
        }

        $customer->update(['status' => 'deleted']);
        return response()->json([
            'message' => 1,
            'data' => $customer
        ], 200);
    }

    public function addCustomersByJson(Request $request, Store $store)
    {
        $customers = $request->input('json_data');

        $newCustomers = [];
        foreach ($customers as $key => $customer) {
            $typeValidator = Validator::make($customer, [
                'name' => 'required|string|max:255',
                'phone' => 'required|string',
                'address' => 'nullable|string',
                'email' => 'nullable|string',
                'payment_info' => 'nullable|string',
                'province' => 'nullable|string',
                'ward' => 'nullable|string',
                'district' => 'nullable|string',
                'points' => 'nullable|numeric'
            ], [
                'unique' => ':attribute đã được sử dụng',
                'required' => ':attribute bị thiếu',
                'string' => 'Kiểu chuỗi',
                'numeric' => 'Kiểu số',
            ]);


            if ($typeValidator->fails()) {
                continue;
            } else {
                if (!$store->customers()->where('id', $customer['phone'])->first()) {
                    array_push($newCustomers, $customer);
                }
            }
        }

        foreach ($newCustomers as $customer) {
            $last_id = count($store->customers);
            $customer_code = 'KH' . sprintf('%06d', $last_id + 1);
            Customer::create(array_merge($customer, ['customer_code' => $customer_code]));
        }

        return response()->json([
            'message' => 'customers added successfully'
        ], 200);
    }
}
