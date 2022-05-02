<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Store;
use App\Models\Branch;
use Illuminate\Support\Facades\DB;

class PaymentReceiptVoucherController extends Controller
{
    public function index(Request $request, Store $store, Branch $branch)
    {
        $limit = $request->query('limit');
        $page = $request->query('page');
        // extract query string
        $start_date = $request->query('startDate');
        $end_date = $request->query('endDate');
        $min_total_amount = $request->query('minTotalAmount');
        $max_total_amount = $request->query('maxTotalAmount');
        
        $user_type = $request->query('userType');
        $user_name = $request->query('userName');
        $type = $request->query('type');
        $order_by = $request->query('orderBy');
        $sort = $request->query('sort');

        // set up query
        $queries = [];
        if ($start_date) {
            array_push($queries, ['payment_receipt_vouchers.date', '>=', $start_date]);
        }

        if ($end_date) {
            array_push($queries, ['payment_receipt_vouchers.date', '<=', $end_date]);
        }

        if ($min_total_amount) {
            array_push($queries, ['payment_receipt_vouchers.value', '>=', $min_total_amount]);
        }

        if ($max_total_amount) {
            array_push($queries, ['payment_receipt_vouchers.value', '<=', $max_total_amount]);
        }

        if ($type) {
            array_push($queries, ['payment_receipt_vouchers.type', '=', $type]);
        }
        if ($user_type) {
            array_push($queries, ['payment_receipt_vouchers.user_type', '=', $user_type]);
        }
        if ($user_name) {
            array_push($queries, ['payment_receipt_vouchers.user_name', 'like',  '%' . $user_name . '%']);
        }

        $database_query = DB::table('payment_receipt_vouchers')
            ->where('branch_id', '=', $branch->id)
            ->where($queries);


        $total_rows = $database_query->get()->count();

        if ($limit) {
            $result = $database_query
                ->orderBy($order_by, $sort)
                ->offset($limit * $page)
                ->limit($limit)
                ->get();
        } else {
            $result = $database_query
                ->orderBy($order_by, $sort)
                ->get();
        }


        return response()->json([
            'data' => $result,
            'total_rows' => $total_rows,
        ], 200);
    }

    public function store(Request $request, Store $store, Branch $branch)
    {
        $validated = $request->validate([
            'value' => 'required|numeric',
            'user_type' => 'nullable|string',
            'user_name' => 'nullable|string',
            'date' => 'required|date_format:Y-m-d',
            'type' => 'required|string',
            'note' => 'nullable|string',
            'is_calculated' => 'required|boolean',
            'branch_id' => 'required|numeric'
        ]);

        $result = $this->create(array_merge($validated, [
            'branch_id' => $branch->id,
        ]));

        
        return response()->json([
            'message' => $result
        ], 200);
    }

    public function delete(Request $request, $id)
    {
        $status = DB::table('payment_receipt_vouchers')->where('id', $id)->delete();
        return response()->json([
            'message' => $status
        ], 200);
    }

    public static function deleteByCode($code)
    {
        $status = DB::table('payment_receipt_vouchers')->where('note', $code)->delete();
    }

    public static function create($data) {
        $last_id = DB::table('payment_receipt_vouchers')
                ->where('branch_id', $data['branch_id'])
                ->where('type', $data['type'])
                ->get()
                ->count();
        $code = 'TC' . sprintf('%06d', $last_id + 1);

        return DB::table('payment_receipt_vouchers')->insert(array_merge(
            $data,
            ['code' => $code]
        ));
    }
}
