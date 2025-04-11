<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Item;
use App\Models\Customer;
use App\Models\CustomerHistory;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use App\Services\SelectedCompanyService;

class InvoicesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $invoices = Invoice::with('items')->latest()->get();

        return response()->json([
            'invoices' => $invoices,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'client_name'            => 'required|string',
            'number'                 => 'required|string',
            'email'                  => 'nullable|email',
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
    
                if (!$item || $item->quantity_count < $itemData['quantity']) {
                    DB::rollBack();
                    return response()->json([
                        'status' => false,
                        'message' => $item
                            ? "Insufficient stock for item '{$item->name}'. Available: {$item->quantity_count}, Requested: {$itemData['quantity']}."
                            : "Item with ID {$itemData['item_id']} not found in store_items table.",
                    ], 422);
                }
            }
    
            $total = collect($data['items'])->sum(fn ($item) => $item['quantity'] * $item['unit_price']);
            $selectedCompany = SelectedCompanyService::getSelectedCompanyOrFail();
    
            $customer = Customer::firstOrCreate(
                [
                    'number'     => $data['number'],
                    'company_id' => $selectedCompany->id,
                ],
                [
                    'name'  => $data['client_name'],
                    'email' => $data['email'] ?? null,
                ]
            );
    
            $invoice = Invoice::create([
                'invoice_number' => Str::uuid(),
                'client_name'    => $data['client_name'],
                'client_email'   => $data['email'] ?? null,
                'invoice_date'   => $data['invoice_date'],
                'total_amount'   => $total,
                'company_id'     => $selectedCompany->id,
            ]);
    
            $purchasedItems = [];
    
            foreach ($data['items'] as $itemData) {
                $item = Item::find($itemData['item_id']);
                $item->quantity_count -= $itemData['quantity'];
                $item->save();
    
                $totalPrice = $itemData['quantity'] * $itemData['unit_price'];
    
                $invoice->items()->create([
                    'item_id'     => $item->id,
                    'description' => $item->name ?? 'Item',
                    'quantity'    => $itemData['quantity'],
                    'unit_price'  => $itemData['unit_price'],
                    'total'       => $totalPrice,
                ]);
    
                $purchasedItems[] = [
                    'description' => $item->name,
                    'quantity'    => $itemData['quantity'],
                    'unit_price'  => $itemData['unit_price'],
                    'total'       => $totalPrice,
                ];
            }
    
            CustomerHistory::create([
                'customer_id'   => $customer->id,
                'items'         => $purchasedItems,
                'purchase_date' => $data['invoice_date'],
                'details'       => 'Purchase recorded from invoice #' . ($invoice->invoice_number ?? '0000'),
                'subtotal'      => $total,
            ]);
    
            DB::commit();
    
            if (!empty($customer->email)) {
                $invoice->load('items');
    
                $pdf = Pdf::loadView('invoices.pdf', [
                    'invoice'        => $invoice,
                    'company_name'   => $selectedCompany->company->company_name,
                    'footer_note'    => 'Thanks',
                    'show_signature' => true,
                ]);
    
                $pdfContent = $pdf->output();
    
                Mail::send([], [], function ($message) use ($customer, $invoice, $pdfContent) {
                    $message->to($customer->email)
                        ->subject('Your Invoice #' . $invoice->invoice_number . ' is ready')
                        ->html('Invoice attached.') 
                        ->attachData($pdfContent, 'invoice_' . $invoice->id . '.pdf', [
                            'mime' => 'application/pdf',
                        ]);
                });
            }
    
            return response()->json([
                'status' => true,
                'message' => 'Invoice created successfully.',
                'invoice' => $invoice->load('items'),
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

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $invoice = Invoice::with('items')->findOrFail($id);
        return response()->json($invoice);
    }

    /**
     * Download the invoice as PDF (generate on-the-fly).
     */
    public function download($id)
    {
        $invoice = Invoice::with('items')->findOrFail($id);
        $selectedCompany = SelectedCompanyService::getSelectedCompanyOrFail();
    
        $pdfData = [
            'invoice'        => $invoice,
            'company_name'   => $selectedCompany->company->company_name,
            'footer_note'    => 'Thanks',
            'show_signature' => true,
        ];
    
        $pdf = Pdf::loadView('invoices.pdf', $pdfData);
        $pdfContent = $pdf->output();
        $pdfBase64 = base64_encode($pdfContent);
    
        return response()->json([
            'status' => true,
            'message' => 'PDF generated successfully.',
            'pdf_base64' => $pdfBase64,
            'filename' => 'invoice_' . $invoice->id . '.pdf',
        ]);
    }
}
