<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Store;
use App\Models\Employee;

class EmployeeController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $store_id = $request->query('store_id');

        if (Store::where('id', $store_id)->doesntExist()) {
            return response()->json(['message' => 'store_id do not exist'], 404);
        }


        return Employee::where('store_id', $store_id)->get();
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $fields = $request->validate([
            'store_id' => 'required|numeric',
            'name' => 'required|string',
            'email' => 'required|string|unique:employees,email',
            'password' => 'required|string|confirmed',
            'phone' => 'required',
            'date_of_birth' => 'required|date_format:Y-m-d',
            'status' =>'required|in:active,inactive',
            'gender' => 'required|in:male,female',
        ]);

        return $employee = Employee::create([
            'name' => $fields['name'],
            'email' => $fields['email'],
            'store_id' => $fields['store_id'],
            'password' => bcrypt($fields['password']),
            'phone' => $fields['phone'],
            'date_of_birth' => $fields['date_of_birth'],
            'status' => $fields['status'],
            'gender' => $fields['gender'],
        ]);

    }


    public function show(Employee $employee)
    {
        return $employee;
    }

    public function update(Request $request, Employee $employee)
    {
        $employee->update($request->all());
        return $employee;
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Employee $employee)
    {
        return Employee::destroy($employee->id);
    }
}
