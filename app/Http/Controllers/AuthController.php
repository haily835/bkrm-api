<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Employee;
use App\Models\Store;
use App\Models\Branch;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Supplier;
use Validator;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function ownerRegister(Request $request)
    {
        $fields = $request->validate([
            'name' => 'required|string',
            'email' => 'required|string|unique:users,email',
            'password' => 'required|string|confirmed',
            'phone' => 'required|unique:users',
            'date_of_birth' => 'nullable|date_format:Y-m-d',
            'status' => 'nullable|in:active,inactive',
            'gender' => 'nullable|string',
            'store_name' => 'required|string',
            'address' => 'required|string',
            'province' => 'required|string',
            'ward' => 'required|string',
            'district' => 'required|string',
            'store_phone' => 'required|string',
            'default_branch' => 'required|boolean',
            'lng' => 'nullable|string',
            'lat' => 'nullable|string',
        ]);

        $user = User::create([
            'uuid' => (string)Str::uuid(),
            'name' => $fields['name'],
            'email' => $fields['email'],
            'password' => bcrypt($fields['password']),
            'phone' => $fields['phone'],
            'date_of_birth' => $fields['date_of_birth'] ? $fields['date_of_birth'] : null,
            'status' => $fields['status'] ? $fields['status'] : "active",
            'gender' => array_key_exists('gender', $fields) ? $fields['gender'] : null,
        ]);

        $store = Store::create([
            'uuid' => (string) Str::uuid(),
            'user_id' => $user->id,
            'name' => $fields['store_name'],
            'address' => $fields['address'],
            'ward' => $fields['ward'],
            'district' => $fields['district'],
            'province' => $fields['province'],
            'phone' => $fields['store_phone'],
            'status' => 'active',
            'image' => 'http://103.163.118.100/bkrm-api/storage/app/public/store-images/store-default.png',
        ]);

        if ($fields['default_branch']) {
            $branch = Branch::create([
                'store_id' => $store->id,
                'uuid' => (string) Str::uuid(),
                'name' => $fields['store_name'],
                'address' => $fields['address'],
                'ward' => $fields['ward'],
                'district' => $fields['district'],
                'province' => $fields['province'],
                'phone' => $fields['store_phone'],
                'status' => 'active',
                'lat' => $fields['lat'],
                'lng' => $fields['lng'],
            ]);
        }

        Category::create([
            "name" => "Danh mục chung",
            "uuid" => (string) Str::uuid(),
            "store_id" => $store->id,

        ]);

        Supplier::create([
            "uuid" => (string) Str::uuid(),
            "name" => "Nhà cung cấp chung",
            "store_id" => $store->id,
        ]);

        Customer::create([
            "uuid" => (string) Str::uuid(),
            "name" => "Khách hàng chung",
            "store_id" => $store->id,
        ]);

        return response()->json([
            'message' => 'User successfully registered',
            'user' => $user,
            'store' => $store,
            'branch' => $branch,

        ], 201);
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required|string',
            'password' => 'required|string',
        ]);

        if ($request['role'] === 'employee') {
            if ($validator->fails()) {
                return response()->json($validator->errors(), 422);
            }

            if (Auth::guard('employee')->attempt($validator->validated())) {
                $user = Auth::guard('employee')->user();
                if ($user->status === "deleted" || $user->status === "inactive") {
                    return response()->json(['error' => 'Unauthorized', 'message' => 'Tài khoản bị ngưng hoạt động hoặc đã xóa'], 401);
                }
            }

            if (!$token = Auth::guard('employee')->attempt($validator->validated())) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }

            return $this->createNewEmpToken($token);
        }
        if ($request['role'] === 'owner') {

            if ($validator->fails()) {
                return response()->json($validator->errors(), 422);
            }

            if (!$token = auth()->attempt($validator->validated())) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }

            return $this->createNewToken($token);
        }
    }

    public function employeeLogin(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'phone' => 'required|string',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        if (!$token = Auth::guard('employee')->attempt($validator->validated())) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        return $this->createNewEmpToken($token);
    }

    protected function createNewToken($token)
    {
        $user = Auth::guard('user')->user();
        $store = Store::where('user_id', $user->id)->get()[0];
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth()->factory()->getTTL() * 60,
            'user' => $user,
            'store' => $store,
            'role' => 'owner'
        ]);
    }

    protected function createNewEmpToken($token)
    {
        $user = Auth::guard('employee')->user();
        $store = Store::where('user_id', $user->store_id)->get()[0];
        // return response()->json([
        //     'access_token' => $token,
        //     'store' => $store,
        //     'token_type' => 'bearer',
        //     'expires_in' => auth()->factory()->getTTL() * 60,
        //     'user' => Auth::guard('employee')->user(),
        //     'permissions' => array_map(function ($p) {
        //             return $p['name'];
        //         }, $user->getAllPermissions()->toArray()),
        // ]);

        return response()->json([
            'access_token' => $token,
            'store' => $store,
            'token_type' => 'bearer',
            'expires_in' => auth()->factory()->getTTL() * 60,
            'user' => Auth::guard('employee')->user(),
            'permissions' => $user->priviledges,
        ]);
    }

    public function refresh()
    {
        return $this->createNewToken(auth()->refresh());
    }

    public function verifyOwnerToken()
    {
        if (Auth::guard('user')->user()) {
            $user = Auth::guard('user')->user();
            $store = Store::where('user_id', $user->id)->get()[0];

            return response()->json([
                'user' => $user,
                'store' => $store,
                'role' => 'owner',
            ]);
        } else if (Auth::guard('employee')->user()) {
            $user = Auth::guard('employee')->user();
            $store = Store::where('user_id', $user->store_id)->get()[0];
            return response()->json([
                'user' => Auth::guard('employee')->user(),
                'store' => $store,
                'role' => 'employee',
                'permission' => $user->priviledges,
            ]);
        } else {
            return response()->json([
                'message' => 'Unauthorized',
            ], 401);
        }
    }

    public function logout()
    {
        auth()->logout();

        return response()->json(['message' => 'User successfully signed out']);
    }
}
