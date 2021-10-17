<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function register(Request $request) {
        $fields = $request->validate([
            'name' => 'required|string',
            'email' => 'required|string|unique:users,email',
            'password' => 'required|string|confirmed',
            'phone' => 'required',
            'date_of_birth' => 'required|date_format:Y-m-d',
            'status' =>'required|in:active,inactive',
            'gender' => 'required|in:male,female',
        ]);

        $user = User::create([
            'name' => $fields['name'],
            'email' => $fields['email'],
            'password' => bcrypt($fields['password']),
            'phone' => $fields['phone'],
            'date_of_birth' => $fields['date_of_birth'],
            'status' => $fields['status'],
            'gender' => $fields['gender'],
        ]);

        $token = $user->createToken('myapptoken')->plainTextToken;

        $response = [
            'user' => $user,
            'api_token' => $token
        ];

        return response($response, 201);
    }

    public function ownerLogin(Request $request) {
        $fields = $request->validate([
            'email' => 'required|string',
            'password' => 'required|string'
        ]);

        // Check email
        $user = User::where('email', $fields['email'])->first();

        // Check password
        if(!$user || !Hash::check($fields['password'], $user->password)) {
            return response([
                'message' => 'Bad creds'
            ], 401);
        }

        $token = $user->createToken('myapptoken')->plainTextToken;

        $response = [
            'user_id' => $user->id,
            'user_name' => $user->name,
            'icon' => "storage/user-male.png",
            'api_token' => $token
        ];

        return response($response, 201);
    }

    public function employeeLogin(Request $request) {
        $fields = $request->validate([
            'email' => 'required|string',
            'password' => 'required|string',
            'store_id' => 'required|numeric',
        ]);

        $employee = Employee::where('store_id', $fields['store_id'])
                            ->where('email', $fields['email'])->first();
        
        // Check password
        if(!$employee || !Hash::check($fields['password'], $employee->password)) {
            return response([
                'message' => 'Bad creds'
            ], 401);
        }

        $token = $employee->createToken('myapptoken')->plainTextToken;

        $response = [
            'employee_id' => $employee->id,
            'name' => $employee->name,
            'api_token' => $token,
        ];

        return response($response, 201);
    }

    // public function logout(Request $request) {
    //     auth()->user()->tokens()->delete();

    //     return [
    //         'message' => 'Logged out'
    //     ];
    // }
}
