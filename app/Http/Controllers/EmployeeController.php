<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Store;
use App\Models\Employee;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Str;

class EmployeeController extends Controller
{
    public function index(Store $store)
    {
        return response()->json([
            'data' => $store->employees
        ], 200);
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
            'id_card_num' => 'nullable|string',
            'salary' => 'nullable|string',
            'salary_type' => 'nullable|string',
            'address' => 'nullable|string',
            'permissions' => 'required|array'
        ]);

        $employee = [
            'name' => $fields['name'],
            'email' => $fields['email'],
            'password' => bcrypt($fields['password']),
            'phone' => $fields['phone'],
            'uuid' => (string) Str::uuid(),
            'date_of_birth' => $fields['date_of_birth'] ? $fields['date_of_birth'] : null,
            'status' => $fields['status'] ?  $fields['status'] : null,
            'gender' => $fields['gender'] ? $fields['gender'] : null,
            'id_card_num' => $fields['id_card_num'],
            'salary' => $fields['salary'],
            'salary_type' => $fields['salary_type'],
            'address' => $fields['address'],
            'store_id' => $store->id,
        ];

        $newEmployee = Employee::create($employee);

        foreach($fields['permissions'] as $permission) {
            $newEmployee->givePermissionTo($permission);
        }

       
        return response()->json([
            'message' => 'Employee created sucessfully',
            'data' => $newEmployee
        ], 200);
    }

    public function update(Request $request, Store $store, Employee $employee)
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

    public function destroy(Store $store, Employee $employee)
    {
        $isDeleted = Employee::destroy($employee->id);
        return response()->json([
            'message' => $isDeleted,
            'data' => $employee,
        ], 200);
    }

    public function show(Store $store, Employee $employee) {
        $permissions = $employee->getAllPermissions();
        return response()->json([
            'data' => array_merge($employee->toArray(), ['permissions' => $permissions])
        ], 200); 
    }

    public function permissions(Request $request, Store $store, Employee $employee) {

        $permissions = $request->validate([
            'manage-employees' => 'nullable|boolean',
            'manage-orders' => 'nullable|boolean',
            'manage-purchase-orders' => 'nullable|boolean',
            'manage-purchase-returns' => 'nullable|boolean',
        ]);

        foreach($permissions as $name => $value) {
            if ($value) {
                $employee->givePermissionTo($name);
            } else {
                $employee->revokePermissionTo($name);
            }
        }

        return response()->json([
            'message' => 'Update permissions successfully',
            'data' => $employee->getAllPermissions(),
        ], 200);
    }

    public function getEmpPermissions(Request $request, Store $store, Employee $employee) {
        return response()->json([
            'data' => $employee->getAllPermissions(),
        ], 200);
    }
}
