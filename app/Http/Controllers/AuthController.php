<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Employee;
use Validator;

class AuthController extends Controller
{
    public function register(Request $request) {
        $fields = $request->validate([
            'name' => 'required|string',
            'email' => 'required|string|unique:users,email',
            'password' => 'required|string|confirmed',
            'phone' => 'required|unique:users',
            'date_of_birth' => 'nullable|date_format:Y-m-d',
            'status' =>'nullable|in:active,inactive',
            'gender' => 'nullable|in:male,female',
        ]);

        $user = User::create([
            'name' => $fields['name'],
            'email' => $fields['email'],
            'password' => bcrypt($fields['password']),
            'phone' => $fields['phone'],
            'date_of_birth' => $fields['date_of_birth'] ? $fields['date_of_birth'] : null,
            'status' => $fields['status'] ? $fields['status'] : "active",
            'gender' => $fields['gender'] ? $fields['gender'] : null,
        ]);

        return response()->json([
            'message' => 'User successfully registered',
            'user' => $user
        ], 201);
    }

    public function ownerLogin(Request $request) {
        $validator = Validator::make($request->all(), [
            'phone' => 'required|string',
            'password' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        if (! $token = auth()->attempt($validator->validated())) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        return $this->createNewToken($token);
    }

    public function employeeLogin(Request $request) {
        
        $validator = Validator::make($request->all(), [
            'phone' => 'required|string',
            'password' => 'required|string',
            'store_id' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        if (! $token = Auth::guard('employee')->attempt($validator->validated())) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        return $this->createNewEmpToken($token);
    }

    protected function createNewToken($token){
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth()->factory()->getTTL() * 60,
            'user' => Auth::guard('user')->user()
        ]);
    }

    protected function createNewEmpToken($token){
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth()->factory()->getTTL() * 60,
            'user' => Auth::guard('employee')->user()
        ]);
    }

    public function refresh() {
        return $this->createNewToken(auth()->refresh());
    }

    public function logout() {
        auth()->logout();

        return response()->json(['message' => 'User successfully signed out']);
    }
}
