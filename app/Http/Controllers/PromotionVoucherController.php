<?php

namespace App\Http\Controllers;

use App\Models\Promotion;
use Illuminate\Http\Request;
use App\Models\Store;
use App\Models\Voucher;
use Illuminate\Support\Facades\DB;

class PromotionVoucherController extends Controller
{
    public function createPromotion(Request $request, Store $store)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'promotion_condition' => 'nullable|string',
            'start_date' => 'nullable|date_format:Y-m-d',
            'end_date' => 'nullable|date_format:Y-m-d',
        ]);

        $last_id = DB::table('promotions')
            ->where('store_id', $store->id)
            ->count();

        $promotion_code = 'KM' . sprintf('%06d', $last_id + 1);

        DB::table('promotions')
            ->insert(array_merge(
                [
                    'store_id' => $store->id,
                    'promotion_code' => $promotion_code,
                ],
                $validated
            ));

        return response()->json([
            'message' => 'success',
            'data' => $promotion_code,
        ]);
    }

    public function createVoucher(Request $request, Store $store)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'start_date' => 'nullable|date_format:Y-m-d',
            'end_date' => 'nullable|date_format:Y-m-d',
            'status' => 'nullable|string',
            'quantity' => 'nullable|numeric',
            'value' => 'required|numeric',
            'min_order_total' => 'nullable|numeric'
        ]);

        $last_id = DB::table('vouchers')
            ->where('store_id', $store->id)
            ->count();

        $voucher_code = 'VC' . sprintf('%06d', $last_id + 1);

        DB::table('promotions')
            ->insert(array_merge(
                [
                    'store_id' => $store->id,
                    'voucher_code' => $voucher_code,
                ],
                $validated
            ));

        return response()->json([
            'message' => 'success',
            'data' => $voucher_code,
        ]);
    }

    public function getActivePromotionVoucher(Request $request, Store $store)
    {
        $current_date = $request->query('date');

        $promotions = DB::table('promotions')
            ->where('store_id', $store->id)
            ->where('start_date', '>=', $current_date)
            ->where('end_date', '<=', $current_date)
            ->where('status', 'active')->get();
        $voucher = DB::table('vouchers')
            ->where('store_id', $store->id)
            ->where('start_date', '>=', $current_date)
            ->where('end_date', '<=', $current_date)
            ->where('status', 'active')->get();

        return response()->json([
            'promotions' => $promotions,
            'vouchers' => $voucher,
        ]);
    }

    public function getAllPromotions(Request $request, Store $store)
    {
        $limit = $request->query("limit") ? $request->query("limit") : 10;
        $page = $request->query("page") ? $request->query("page") : 1;

        $promotions = DB::table('promotions')
            ->where('store_id', $store->id)
            ->where('status', '<>', 'deleted')
            ->orderBy('created_at', 'desc')
            ->offset($limit * ($page - 1))
            ->limit($limit)
            ->get();


        return response()->json([
            'promotions' => $promotions,
            'message' => 'success',
        ]);
    }

    public function getAllVouchers(Request $request, Store $store)
    {
        $limit = $request->query("limit") ? $request->query("limit") : 10;
        $page = $request->query("page") ? $request->query("page") : 1;

        $vouchers = DB::table('vouchers')
            ->where('store_id', $store->id)
            ->where('status', '<>', 'deleted')
            ->orderBy('created_at', 'desc')
            ->offset($limit * ($page - 1))
            ->limit($limit)
            ->get();


        return response()->json([
            'vouchers' => $vouchers,
            'message' => 'success',
        ]);
    }

    public function updatePromotion(Request $request, Store $store, Promotion $promotion)
    {
        $validated = $request->validate([
            'name' => 'nullable|string',
            'promotion_condition' => 'nullable|string',
            'start_date' => 'nullable|date_format:Y-m-d',
            'end_date' => 'nullable|date_format:Y-m-d',
            'status' => 'nullable|string',
        ]);

        DB::table('promotions')
            ->where('id', $promotion->id)
            ->update($validated);

        return response()->json([
            'message' => 'success',
        ]);
    }

    public function updateVoucher(Request $request, Store $store, Voucher $voucher)
    {
        $validated = $request->validate([
            'name' => 'nullable|string',
            'start_date' => 'nullable|date_format:Y-m-d',
            'end_date' => 'nullable|date_format:Y-m-d',
            'status' => 'nullable|string',
            'quantity' => 'nullable|numeric',
            'value' => 'nullable|numeric',
            'min_order_total' => 'nullable|numeric'
        ]);

        DB::table('vouchers')
            ->where('id', $voucher->id)
            ->update($validated);

        return response()->json([
            'message' => 'success',
        ]);
    }
}
