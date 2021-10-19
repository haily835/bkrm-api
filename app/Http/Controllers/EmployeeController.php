<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Store;
use App\Models\Employee;

class EmployeeController extends Controller
{
    public function index(Store $store)
    {
        return $store->employees;
    }

    public function store(Request $request, Store $store)
    {
        $fields = $request->validate([
            'name' => 'required|string',
            'email' => 'required|string|unique:employees,email',
            'password' => 'required|string|confirmed',
            'phone' => 'required|string|unique:employees',
            'date_of_birth' => 'nullable|date_format:Y-m-d',
            'status' =>'nullable|in:active,inactive',
            'gender' => 'nullable|in:male,female',
        ]);

        $employee = [
            'username' => $fields['name'],
            'name' => $fields['name'],
            'email' => $fields['email'],
            'password' => bcrypt($fields['password']),
            'phone' => $fields['phone'],
            'date_of_birth' => $fields['date_of_birth'] ? $fields['date_of_birth'] : null,
            'status' => $fields['status'] ?  $fields['status'] : null,
            'gender' => $fields['gender'] ? $fields['gender'] : null,
        ];

        $store->employees()->create($employee);

        return response()->json([
            'message' => 'Employee created sucessfully',
            'data' => $employee,
        ]);
    }

    public function update(Employee $employee)
    {
        $fields = $request->validate([
            'name' => 'nullable|string',
            'email' => 'nullable|string|unique:employees,email',
            'password' => 'nullable|string|confirmed',
            'phone' => 'nullable|string|unique:employees',
            'date_of_birth' => 'nullable|date_format:Y-m-d',
            'status' =>'nullable|in:active,inactive',
            'gender' => 'nullable|in:male,female',
        ]);

        $employee->update($fields);

        return response()->json([
            'message' => 'Employee updated sucessfully',
            'data' => $employee,
        ]);
    }

    public function destroy(Employee $employee)
    {
        return Employee::destroy($employee->id);
    }
}
