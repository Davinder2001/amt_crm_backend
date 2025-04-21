<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Item;
use App\Models\Customer;
use App\Models\ItemVariant;
use App\Models\CustomerHistory;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Services\SelectedCompanyService;

class InvoicesController extends Controller
{
    /**
     * List all invoices (JSON only).
     */
    public function index()
    {
        $invoices = Invoice::with('items')
            ->where('company_id',
            SelectedCompanyService::getSelectedCompanyOrFail()->company->id)
            ->latest()->get();

        return response()->json([
            'status'   => true,
            'invoices' => $invoices,
        ]);
    }

    /**
     * Create a new invoice (JSON only).
     */
    public function store(Request $request)
    {
        [$invoice] = $this->createInvoiceAndPdf($request);

        return response()->json([
            'status'  => true,
            'message' => 'Invoice created successfully.',
            'invoice' => $invoice,
        ], 201);
    }

    /**
     * Create a new invoice and stream the PDF inline.
     */
    public function storeAndPrint(Request $request)
    {
        [$invoice, $pdfContent] = $this->createInvoiceAndPdf($request);

        return response($pdfContent, 200)
            ->header('Content-Type', 'application/pdf')
            ->header(
                'Content-Disposition',
                'inline; filename="invoice_' . $invoice->invoice_number . '.pdf"'
            );
    }

    /**
     * Create a new invoice, email the PDF, and return JSON.
     */
    public function storeAndMail(Request $request)
    {
        [$invoice, $pdfContent] = $this->createInvoiceAndPdf($request);

        $selectedCompany = SelectedCompanyService::getSelectedCompanyOrFail();
        $companyName     = $selectedCompany->company->company_name;
        $issuedByName    = Auth::user()->name;

        if ($invoice->client_email) {
            Mail::send(
                'invoices.pdf',
                [
                    'invoice'        => $invoice,
                    'company_name'   => $companyName,
                    'issued_by'      => $issuedByName,
                    'footer_note'    => 'Thank you for your business',
                    'show_signature' => true,
                ],
                function ($message) use ($invoice, $pdfContent) {
                    $message->to($invoice->client_email)
                        ->subject('Your Invoice #' . $invoice->invoice_number)
                        ->attachData(
                            $pdfContent,
                            'invoice_' . $invoice->invoice_number . '.pdf',
                            ['mime' => 'application/pdf']
                        );
                }
            );
        }

        return response()->json([
            'status'  => true,
            'message' => 'Invoice created and emailed successfully.',
            'invoice' => $invoice,
        ], 201);
    }

    /**
     * Download (or stream) an existing invoice as PDF.
     */
    public function download($id)
    {
        $invoice            = Invoice::with('items')->findOrFail($id);
        $selectedCompany    = SelectedCompanyService::getSelectedCompanyOrFail();
        $companyName        = $selectedCompany->company->company_name;
        $issuedByName       = Auth::user()->name;

        $pdf = Pdf::loadView('invoices.pdf', [
            'invoice'        => $invoice,
            'company_name'   => $companyName,
            'issued_by'      => $issuedByName,
            'footer_note'    => 'Thank you for your business',
            'show_signature' => true,
        ]);

        return $pdf->download('invoice_' . $invoice->invoice_number . '.pdf');
    }

    /**
     * Core creation + PDF generation routine.
     *
     * @return array [Invoice, string $pdfBinary]
     */
    private function createInvoiceAndPdf(Request $request): array
    {
        $validator = Validator::make($request->all(), [
            'client_name'        => 'required|string',
            'number'             => 'required|string',
            'email'              => 'nullable|email',
            'invoice_date'       => 'required|date',
            'payment_method'     => 'required|string',
            'discount_price'     => 'nullable|numeric|min:0',
            'items'              => 'required|array|min:1',
            'items.*.item_id'    => 'required|exists:store_items,id',
            'items.*.variant_id' => 'nullable|exists:item_variants,id',
            'items.*.quantity'   => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            abort(response()->json([
                'status'  => false,
                'message' => 'Validation failed.',
                'errors'  => $validator->errors(),
            ], 422));
        }

        $data            = $validator->validated();
        $selectedCompany = SelectedCompanyService::getSelectedCompanyOrFail();
        $issuedById      = Auth::id();

        $invoice = DB::transaction(function () use ($data, $selectedCompany, $issuedById) {
            
            foreach ($data['items'] as $line) {
                $item = Item::findOrFail($line['item_id']);
                if ($item->quantity_count < $line['quantity']) {
                    throw new \Exception("Insufficient stock for '{$item->name}'.");
                }
            }

            $subtotal = collect($data['items'])->sum(function($line) {
                $item = Item::findOrFail($line['item_id']);
                if (!empty($line['variant_id'])) {

                    $variant = ItemVariant::where('id', $line['variant_id'])->where('item_id', $item->id)->firstOrFail();
                    return $variant->price * $line['quantity'];
                
                }
                return $item->selling_price * $line['quantity'];
            });

            $discountAmount     = $data['discount_price'] ?? 0;
            $discountPercentage = $subtotal ? round(($discountAmount / $subtotal) * 100, 2) : 0;
            $finalAmount        = max(0, $subtotal - $discountAmount);

            $customer = Customer::firstOrCreate(
                ['number' => $data['number'], 'company_id' => $selectedCompany->id],
                ['name'   => $data['client_name'], 'email' => $data['email'] ?? null]
            );

            $inv = Invoice::create([
                'invoice_number'      => Str::uuid(),
                'client_name'         => $data['client_name'],
                'client_email'        => $data['email'] ?? null,
                'invoice_date'        => $data['invoice_date'],
                'total_amount'        => $subtotal,
                'discount_amount'     => $discountAmount,
                'discount_percentage' => $discountPercentage,
                'final_amount'        => $finalAmount,
                'payment_method'      => $data['payment_method'],
                'issued_by'           => $issuedById,
                'company_id'          => $selectedCompany->id,
            ]);

            $historyItems = [];

            foreach ($data['items'] as $line) {

                $item = Item::findOrFail($line['item_id']);

                if (!empty($line['variant_id'])) {

                    $variant   = ItemVariant::where('id', $line['variant_id'])->where('item_id', $item->id)->firstOrFail();
                    $unitPrice = $variant->price;
                
                } else {

                    $unitPrice = $item->selling_price;
                
                }

                $item->decrement('quantity_count', $line['quantity']);
                $lineTotal = $unitPrice * $line['quantity'];

                $inv->items()->create([
                    'item_id'     => $item->id,
                    'variant_id'  => $line['variant_id'] ?? null,
                    'description' => $item->name,
                    'quantity'    => $line['quantity'],
                    'unit_price'  => $unitPrice,
                    'total'       => $lineTotal,
                ]);

                $historyItems[] = [
                    'description' => $item->name,
                    'quantity'    => $line['quantity'],
                    'unit_price'  => $unitPrice,
                    'total'       => $lineTotal,
                ];
            }

            CustomerHistory::create([
                'customer_id'   => $customer->id,
                'items'         => $historyItems,
                'purchase_date' => $data['invoice_date'],
                'details'       => 'Invoice #' . $inv->invoice_number,
                'subtotal'      => $subtotal,
            ]);

            return $inv;
        });

        $invoice->load('items.variant');

        $pdf = Pdf::loadView('invoices.pdf', [
            'invoice'        => $invoice,
            'company_name'   => $selectedCompany->company->company_name,
            'issued_by'      => Auth::user()->name,
            'footer_note'    => 'Thank you for your business',
            'show_signature' => true,
        ]);

        return [$invoice, $pdf->output()];
    }


    /**
     * Show a single invoice as JSON.
     */
    public function show($id)
    {
        $invoice = Invoice::with('items')->findOrFail($id);

        return response()->json([
            'status'  => true,
            'invoice' => $invoice,
        ]);
    }
}
