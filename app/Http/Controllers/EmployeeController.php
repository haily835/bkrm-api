<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Store;
use App\Models\Employee;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Intervention\Image\Facades\Image;

class EmployeeController extends Controller
{
    public function index(Store $store)
    {
        
        return response()->json([
            'data' => $store->employees()->where('status', '<>', 'deleted')->get()
        ], 200);
    }

    public function editEmployeeImage(Request $request, Store $store, Employee $employee) {
        $fields = $request->validate([
            'image' => 'required',
            'oldImageUrl' => 'required',
        ]);

        $imagePath = $fields['image']->store('employee-images', 'public');
        $sized_image = Image::make(public_path("storage/{$imagePath}"))->fit(1000, 1000);
        $sized_image->save();


        /// to do delete old image

        $employee->update(['img_url' => $imagePath]);

        return response()->json([
            'data' => $employee,
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
            'permissions' => 'required|array',
            'image' => 'nullable'
        ]);

        $imagePath = "";
        if (array_key_exists('image', $fields)) {
            if ($fields['image'] != null) {
                $imagePath = $fields['image']->store('employee-images', 'public');
                $sized_image = Image::make(public_path("storage/{$imagePath}"))->fit(1000, 1000);
                $sized_image->save();
            }
        } else {
            $imagePath = 'http://103.163.118.100/bkrm-api/storage/app/public/storage/employee-images/employee-default.png';
        }

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
            'img_url' => $imagePath,
        ];

        $newEmployee = Employee::create($employee);

        foreach($fields['permissions'] as $permission) {
            DB::table('employee_priviledge')->insert(
                ['employee_id' => $newEmployee->id, 'priviledge_id' => $permission]
            );
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
            'email' => 'nullable|string',
            'password' => 'nullable|string|confirmed',
            'phone' => 'nullable|string',
            'date_of_birth' => 'nullable|date_format:Y-m-d',
            'status' =>'nullable|in:active,inactive',
            'gender' => 'nullable|in:male,female',
            'status' => 'nullable|string',
        ]);

        $employee->update($fields);

        return response()->json([
            'message' => 'Employee updated sucessfully',
            'data' => $employee,
        ]);
    }

    public function destroy(Store $store, Employee $employee)
    {
        $employee->update(['status', 'deleted']);
        return response()->json([
            'message' => 1,
            'data' => $employee,
        ], 200);
    }

    public function show(Store $store, Employee $employee) {
        // $permissions = $employee->getAllPermissions();
        return response()->json([
            'data' => array_merge($employee->toArray(), ['permissions' => $employee->priviledges])
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
