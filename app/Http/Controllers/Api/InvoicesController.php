<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Item;
use App\Models\Customer;
use App\Models\ItemVariant;
use App\Models\CustomerHistory;
use App\Models\CustomerCredit;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Services\SelectedCompanyService;

class InvoicesController extends Controller
{
    public function index()
    {
        $invoices = Invoice::with(['items', 'credit'])
            ->where('company_id', SelectedCompanyService::getSelectedCompanyOrFail()->company->id)
            ->latest()->get();

        return response()->json([
            'status'   => true,
            'invoices' => $invoices,
        ]);
    }

    public function store(Request $request)
    {
        [$invoice] = $this->createInvoiceAndPdf($request);

        return response()->json([
            'status'  => true,
            'message' => 'Invoice created successfully.',
            'invoice' => $invoice,
        ], 201);
    }

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

    public function download($id)
    {
        $invoice         = Invoice::with('items')->findOrFail($id);
        // dd($invoice->items);
        $selectedCompany = SelectedCompanyService::getSelectedCompanyOrFail();
        $companyName     = $selectedCompany->company->company_name;
        $issuedByName    = Auth::user()->name;

        $pdf = Pdf::loadView('invoices.pdf', [
            'invoice'          => $invoice,
            'company_name'     => $companyName,
            'company_address'  => $selectedCompany->company->address ?? 'N/A',
            'company_phone'    => $selectedCompany->company->phone ?? 'N/A',
            'company_gstin'    => $selectedCompany->company->gstin ?? 'N/A',
            'issued_by'        => $issuedByName,
            'footer_note'      => 'Thank you for your business',
            'show_signature'   => true,
        ]);
        

        return $pdf->download('invoice_' . $invoice->invoice_number . '.pdf');
    }

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

            $subtotal = collect($data['items'])->sum(function ($line) {
                $item = Item::findOrFail($line['item_id']);
            
                // Step 1: Determine base price
                $price = $item->selling_price;
            
                if (!empty($line['variant_id'])) {
                    $variant = ItemVariant::where('id', $line['variant_id'])
                        ->where('item_id', $item->id)
                        ->firstOrFail();
                    $price = $variant->price;
                }
            
                // Step 2: Get tax rate from item_tax table
                $taxRate = DB::table('item_tax')
                    ->join('taxes', 'item_tax.tax_id', '=', 'taxes.id')
                    ->where('item_tax.store_item_id', $item->id)
                    ->value('taxes.rate') ?? 0;
            
                $priceWithTax = $price + ($price * $taxRate / 100);
                return $priceWithTax * $line['quantity'];
            });
            


            $discountAmount     = $data['discount_price'] ?? 0;
            $discountPercentage = $subtotal ? round(($discountAmount / $subtotal) * 100, 2) : 0;
            $finalAmount        = max(0, $subtotal - $discountAmount);

            $customer = Customer::firstOrCreate(
                ['number' => $data['number'], 'company_id' => $selectedCompany->id],
                ['name' => $data['client_name'], 'email' => $data['email'] ?? null]
            );

            $companyCode = $selectedCompany->company->company_id;
            $datePrefix  = now()->format('Ymd');
            $last        = Invoice::where('company_id', $selectedCompany->id)
                ->whereDate('invoice_date', now()->toDateString())
                ->orderBy('invoice_number', 'desc')
                ->first();

            $nextSeq = $last ? ((int) substr($last->invoice_number, -4)) + 1 : 1;
            $invoiceNumber = "{$companyCode}{$datePrefix}" . str_pad($nextSeq, 4, '0', STR_PAD_LEFT);

            

            $inv = Invoice::create([
                'invoice_number'      => $invoiceNumber,
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

                $unitPrice = !empty($line['variant_id'])
                    ? ItemVariant::where('id', $line['variant_id'])
                    ->where('item_id', $item->id)->firstOrFail()->price
                    : $item->selling_price;

                $item->decrement('quantity_count', $line['quantity']);
                $lineTotal = $unitPrice * $line['quantity'];

                $taxPercentage = DB::table('item_tax')
                ->join('taxes', 'item_tax.tax_id', '=', 'taxes.id')
                ->where('item_tax.store_item_id', $item->id)
                ->value('taxes.rate') ?? 0;

                $taxAmount = $unitPrice * $taxPercentage / 100;

                $totalAmount = $lineTotal + $taxAmount;

                $inv->items()->create([
                    'item_id'        => $item->id,
                    'variant_id'     => $line['variant_id'] ?? null,
                    'description'    => $item->name,
                    'quantity'       => $line['quantity'],
                    'unit_price'     => $unitPrice,
                    'tax_percentage' => $taxPercentage,
                    'tax_amount'     => $taxAmount,
                    'total'          => $totalAmount,
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

            // Create Credit Record
            if ($finalAmount > 0) {
                CustomerCredit::create([
                    'customer_id'  => $customer->id,
                    'invoice_id'   => $inv->id,
                    'total_due'    => $finalAmount,
                    'amount_paid'  => 0,
                    'outstanding'  => $finalAmount,
                    'status'       => 'due',
                ]);
            }

            return $inv;
        });

        $invoice->load('items.variant', 'credit');

        $pdf = Pdf::loadView('invoices.pdf', [
            'invoice'          => $invoice,
            'company_name'     => $selectedCompany->company->company_name,
            'company_address'  => $selectedCompany->company->address ?? 'N/A',
            'company_phone'    => $selectedCompany->company->phone ?? 'N/A',
            'company_gstin'    => $selectedCompany->company->gstin ?? 'N/A',
            'issued_by'        => Auth::user()->name,
            'footer_note'      => 'Thank you for your business',
            'show_signature'   => true,
        ]);
        

        return [$invoice, $pdf->output()];
    }

    public function show($id)
    {
        $invoice = Invoice::with('items', 'credit')->findOrFail($id);

        return response()->json([
            'status'  => true,
            'invoice' => $invoice,
        ]);
    }
}
