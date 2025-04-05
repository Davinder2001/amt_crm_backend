<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Item;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class InvoicesController extends Controller
{

    public function index()
    {
        $invoices = Invoice::with('items')->latest()->get();

        return response()->json([
            'invoices' => $invoices
        ]);
    }

    public function store(Request $request)
{
    $validator = Validator::make($request->all(), [
        'client_name'            => 'required|string',
        'invoice_date'           => 'required|date',
        'items'                  => 'required|array',
        'items.*.item_id'        => 'required|exists:store_items,id',
        'items.*.quantity'       => 'required|integer|min:1',
        'items.*.unit_price'     => 'required|numeric|min:0',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'status' => false,
            'message' => 'Validation failed',
            'errors' => $validator->errors(),
        ], 422);
    }

    $data = $validator->validated();

    $total = collect($data['items'])->sum(function ($item) {
        return $item['quantity'] * $item['unit_price'];
    });

    $invoice = Invoice::create([
        'invoice_number' => Str::uuid(),
        'client_name'    => $data['client_name'],
        'invoice_date'   => $data['invoice_date'],
        'total_amount'   => $total,
    ]);

    foreach ($data['items'] as $itemData) {
        $item = Item::find($itemData['item_id']);

        if (!$item) {
            return response()->json([
                'status' => false,
                'message' => "Item with ID {$itemData['item_id']} not found.",
            ], 422);
        }

        $invoice->items()->create([
            'description' => $item->name, // ✅ Get name from store_items
            'quantity'    => $itemData['quantity'],
            'unit_price'  => $itemData['unit_price'],
            'total'       => $itemData['quantity'] * $itemData['unit_price'],
        ]);
    }

    $pdf = Pdf::loadView('invoices.pdf', ['invoice' => $invoice->load('items')]);
    $pdfPath = "invoices/invoice_{$invoice->id}.pdf";
    Storage::put("public/{$pdfPath}", $pdf->output());

    $invoice->update(['pdf_path' => $pdfPath]);

    return response()->json([
        'status' => true,
        'message' => 'Invoice created successfully.',
        'invoice' => $invoice->load('items'),
        'pdf_url' => url("storage/{$pdfPath}")
    ], 201);
}
    public function show($id)
    {
        $invoice = Invoice::with('items')->findOrFail($id);
        return response()->json($invoice);
    }

    public function download($id)
    {
        $invoice = Invoice::findOrFail($id);
        return Storage::download("public/{$invoice->pdf_path}");
    }
}