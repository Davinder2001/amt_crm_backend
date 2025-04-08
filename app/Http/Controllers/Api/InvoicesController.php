<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Item;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use App\Services\SelectedCompanyService;

class InvoicesController extends Controller
{

    public function index()
    {
        $invoices = Invoice::with('items')->latest()->get();

        $invoices->transform(function ($invoice) {
            if ($invoice->pdf_path && File::exists(public_path($invoice->pdf_path))) {
                $pdfContent = File::get(public_path($invoice->pdf_path));
                $invoice->pdf_base64 = base64_encode($pdfContent);
            } else {
                $invoice->pdf_base64 = null;
            }

            return $invoice;
        });

        return response()->json([
            'invoices' => $invoices,
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

        DB::beginTransaction();

        try {

            foreach ($data['items'] as $itemData) {
                $item = Item::find($itemData['item_id']);

                if (!$item) {
                    DB::rollBack();
                    return response()->json([
                        'status' => false,
                        'message' => "Item with ID {$itemData['item_id']} not found in store_items table.",
                    ], 422);
                }

                if ($item->quantity_count < $itemData['quantity']) {
                    DB::rollBack();
                    return response()->json([
                        'status' => false,
                        'message' => "Insufficient stock for item '{$item->name}'. Available: {$item->quantity}, Requested: {$itemData['quantity']}.",
                    ], 422);
                }
            }

            $total = collect($data['items'])->sum(function ($item) {
                return $item['quantity'] * $item['unit_price'];
            });


            $selectedCompany = SelectedCompanyService::getSelectedCompanyOrFail();

            $invoice = Invoice::create([
                'invoice_number' => Str::uuid(),
                'client_name'    => $data['client_name'],
                'invoice_date'   => $data['invoice_date'],
                'total_amount'   => $total,
                'company_id'     => $selectedCompany->id,
            ]);

            foreach ($data['items'] as $itemData) {

                $item = Item::find($itemData['item_id']);
                $item->quantity_count -= $itemData['quantity'];
                $item->save();

                $invoice->items()->create([
                    'item_id'     => $item->id,
                    'description' => $item->name ?? 'Item',
                    'quantity'    => $itemData['quantity'],
                    'unit_price'  => $itemData['unit_price'],
                    'total'       => $itemData['quantity'] * $itemData['unit_price'],
                ]);
            }

            $invoice->load('items');
            $pdf = Pdf::loadView('invoices.pdf', ['invoice' => $invoice]);
            $pdfContent = $pdf->output();

            $pdfPath = "invoices/invoice_{$invoice->id}.pdf";
            File::ensureDirectoryExists(public_path('invoices'));
            file_put_contents(public_path($pdfPath), $pdfContent);

            $invoice->update(['pdf_path' => $pdfPath]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Invoice created successfully.',
                'invoice' => $invoice,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => false,
                'message' => 'An error occurred while creating the invoice.',
                'error' => $e->getMessage(),
            ], 500);
        }
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
