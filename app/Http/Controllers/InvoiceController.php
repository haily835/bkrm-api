<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    public function index(Request $request, Store $store, Branch $branch)
    {
        return response()->json([
            'data' => $branch->invoices
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, Store $store, Branch $branch)
    {
        $validated = $request->validate([
            'order_id' => "required|numeric",
            'due_date' => "required|date_format:Y-m-d",
            'tax' => "required|numeric",
            'shipping' => 'required|numeric',
            'amount_due' => 'required|numeric',
            'discount' => 'required|numeric',
        ]);

        $invoice = array_merge($validated, [
            'store_id' => $store->id,
            'branch_id' => $branch->id
        ]);
        
        Invoice::create($invoice);

        return response()->json([
            'message' => 'Invoice created',
            'data' => $invoice,
        ], 200);
    }

    public function show(Invoice $invoice)
    {
        return $invoice;
    }

    public function update(Request $request, Invoice $invoice)
    {
        $validated = $request->validate([
            'due_date' => "nullable|date_format:Y-m-d",
            'tax' => "nullable|numeric",
            'shipping' => 'nullable|numeric',
            'amount_due' => 'nullable|numeric',
            'discount' => 'nullable|numeric',
        ]);

        
        return response()->json([
            'message' => 'Invoice created',
            'data' => $invoice->update($validated),
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Invoice  $invoice
     * @return \Illuminate\Http\Response
     */
    public function destroy(Invoice $invoice)
    {
        return Invoice::destroy($invoice->id);
    }
}
