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
        [$invoice, $pdfContent] = $this->createInvoiceAndPdf($request);

        return response()->json([
            'status'  => true,
            'message' => 'Invoice saved successfully.',
            'invoice' => $invoice,
        ], 201);
    }

    /**
     * Save + return PDF inline (for printing)
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
     * Save + send email with PDF attachment
     */
    // use Illuminate\Support\Facades\Mail;
    // use App\Services\SelectedCompanyService;
    
    public function storeAndMail(Request $request)
    {
        // 1) Create the invoice and generate its PDF
        [$invoice, $pdfContent] = $this->createInvoiceAndPdf($request);
    
        // 2) Resolve company name for the view
        $selectedCompany = SelectedCompanyService::getSelectedCompanyOrFail();
        $companyName = $selectedCompany->company->company_name;
    
        // 3) If the customer has an email, send it
        if (! empty($invoice->client_email)) {
            Mail::send(
                // Use your PDF-view as the email HTML body
                'invoices.pdf',
                [
                    'invoice'        => $invoice,
                    'company_name'   => $companyName,
                    'footer_note'    => 'Thank you for your business',
                    'show_signature' => true,
                ],
                function ($message) use ($invoice, $pdfContent) {
                    $message->to($invoice->client_email)
                            ->subject('Your Invoice #' . $invoice->invoice_number)
                            // attach the already‑generated PDF
                            ->attachData(
                                $pdfContent,
                                'invoice_' . $invoice->invoice_number . '.pdf',
                                ['mime' => 'application/pdf']
                            );
                }
            );
        }
    
        // 4) Return JSON
        return response()->json([
            'status'  => true,
            'message' => 'Invoice saved and emailed successfully.',
            'invoice' => $invoice,
        ], 201);
    }
    
    

    /**
     * Core creation + PDF generation
     *
     * @return array[Invoice $invoice, string $pdfContent]
     */
    private function createInvoiceAndPdf(Request $request): array
    {
        $validator = Validator::make($request->all(), [
            'client_name'         => 'required|string',
            'number'              => 'required|string',
            'email'               => 'nullable|email',
            'invoice_date'        => 'required|date',
            'items'               => 'required|array|min:1',
            'items.*.item_id'     => 'required|exists:store_items,id',
            'items.*.quantity'    => 'required|integer|min:1',
            'items.*.unit_price'  => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            abort(response()->json([
                'status'  => false,
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422));
        }

        $data = $validator->validated();
        $selectedCompany = SelectedCompanyService::getSelectedCompanyOrFail();

        $invoice = DB::transaction(function () use ($data, $selectedCompany) {
            foreach ($data['items'] as $itemData) {
                $item = Item::find($itemData['item_id']);
                if (!$item || $item->quantity_count < $itemData['quantity']) {
                    throw new \Exception(
                        $item
                            ? "Insufficient stock for '{$item->name}'."
                            : "Item ID {$itemData['item_id']} not found."
                    );
                }
            }

            $total = collect($data['items'])->sum(fn ($i) => $i['quantity'] * $i['unit_price']);

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

            $inv = Invoice::create([
                'invoice_number' => Str::uuid(),
                'client_name'    => $data['client_name'],
                // 'client_email'   => $data['email'] ?? null,
                'client_email'   => 'davinder02102001@gmail.com',
                'invoice_date'   => $data['invoice_date'],
                'total_amount'   => $total,
                'company_id'     => $selectedCompany->id,
            ]);

            $historyItems = [];
            foreach ($data['items'] as $itemData) {
                $item = Item::find($itemData['item_id']);
                $item->decrement('quantity_count', $itemData['quantity']);

                $lineTotal = $itemData['quantity'] * $itemData['unit_price'];
                $inv->items()->create([
                    'item_id'     => $item->id,
                    'description' => $item->name,
                    'quantity'    => $itemData['quantity'],
                    'unit_price'  => $itemData['unit_price'],
                    'total'       => $lineTotal,
                ]);

                $historyItems[] = [
                    'description' => $item->name,
                    'quantity'    => $itemData['quantity'],
                    'unit_price'  => $itemData['unit_price'],
                    'total'       => $lineTotal,
                ];
            }

            CustomerHistory::create([
                'customer_id'   => $customer->id,
                'items'         => $historyItems,
                'purchase_date' => $data['invoice_date'],
                'details'       => 'Invoice #' . $inv->invoice_number,
                'subtotal'      => $total,
            ]);

            return $inv;
        });

        $invoice->load('items');
        $pdf = PDF::loadView('invoices.pdf', [
            'invoice'      => $invoice,
            'company_name' => $selectedCompany->company->company_name,
            'footer_note'  => 'Thank you for your business',
            'show_signature' => true,
        ]);

        return [$invoice, $pdf->output()];
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
