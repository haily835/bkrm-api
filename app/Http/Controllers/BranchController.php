<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Store;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Employee;

class BranchController extends Controller
{

    public function index(Store $store)
    {
        if (Auth::guard('user')->user()) {
            return response()->json([
                'data' => $store->branches()->where('status', '<>', 'deleted')->get(),
            ]);
        } else if (Auth::guard('employee')->user()) {
            $employee_id = Auth::guard('employee')->user()->id;
            $branches = DB::table('employee_work_branch')
                ->leftJoin('branches', 'branches.id', '=', 'employee_work_branch.branch_id')
                ->where('employee_work_branch.employee_id', $employee_id)
                ->where('branches.status','active')
                ->select('branches.*')
                ->get();

            return response()->json([
                'data' => $branches,
                'emp' => $employee_id,
            ]);
        } else {
            return response()->json([
                'message' => 'Unauthorized',
            ], 401);
        }
    }

    public function getAllBranches(Store $store)
    {
        return response()->json([
            'data' => $store->branches()->where('status', '<>', 'deleted')->get(),
        ]);
    }

    public function store(Request $request, Store $store)
    {
        $data = $request->validate([
            'name' => 'required|unique:stores',
            'address' => 'nullable|string',
            'ward' => 'nullable|string',
            'district' => 'nullable|string',
            'province' => 'nullable|string',
            'phone' => 'nullable|string',
            'status' => 'nullable|in:active,inactive',
            'lat' => 'nullable|string',
            'lng' => 'nullable|string',
        ]);

        $branch = Branch::create(array_merge($data, [
            'store_id' => $store->id,
            'uuid' => (string) Str::uuid(),
        ]));

        return response()->json([
            'message' => 'Branch created successfully',
            'data' => $branch,
        ], 200);
    }

    public function show(Store $store, Branch $branch)
    {
        return response()->json([
            'data' => $branch,
        ], 200);
    }

    public function update(Request $request, Store $store, Branch $branch)
    {
        $data = $request->validate([
            'name' => 'nullable|unique:stores',
            'address' => 'nullable|string',
            'ward' => 'nullable|string',
            'district' => 'nullable|string',
            'province' => 'nullable|string',
            'phone' => 'nullable|string',
            'status' => 'nullable|in:active,inactive',
            'lng' => 'nullable|string',
            'lat' => 'nullable|string',
        ]);

        $branch->update($data);

        return response()->json([
            'message' => 'Branch update successfully',
            'data' => $branch,
        ], 200);
    }

    public function destroy(Store $store, Branch $branch)
    {
        $numOfBranch = $store->branches()->where('status', 'active')->count();
        if ($numOfBranch <= 1) {
            return response()->json([
                'message' => 'Can not delete last branch',
                'data' => $branch
            ], 404);
        }

        $branch->update(['status' => 'deleted']);
        return response()->json([
            'message' => 1,
            'data' => $branch,
        ], 200);
    }
}
