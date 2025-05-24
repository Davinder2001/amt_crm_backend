<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Item;
use Illuminate\Support\Facades\Log;
use App\Models\Customer;
use App\Models\Package;
use App\Models\ItemVariant;
use App\Models\CustomerHistory;
use App\Models\CustomerCredit;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Services\SelectedCompanyService;

class InvoicesController extends Controller
{
    /**
     * Show a list of all attributes with their values.
     */
    public function index()
    {
        $invoices   = Invoice::with(['items', 'credit'])
                    ->where('company_id', SelectedCompanyService::getSelectedCompanyOrFail()
                    ->company->id)->latest()->get();

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
        $logoFile        = $selectedCompany->company->company_logo;

        if (!empty($logoFile) && !is_dir(public_path($logoFile))) {
            $companyLogo = public_path($logoFile);
        } else {
            $companyLogo = null;
        }

        if ($invoice->client_email) {
            Mail::send(
                'invoices.pdf',
                [
                    'invoice'          => $invoice,
                    'company_name'     => $companyName,
                    'company_logo'     => $companyLogo,
                    'company_address'  => $selectedCompany->company->address ?? 'N/A',
                    'company_phone'    => $selectedCompany->company->phone ?? 'N/A',
                    'company_gstin'    => $selectedCompany->company->gstin ?? 'N/A',
                    'footer_note'      => 'Thank you for your business',
                    'show_signature'   => true,
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
        $selectedCompany = SelectedCompanyService::getSelectedCompanyOrFail();
        $companyName     = $selectedCompany->company->company_name;
        $logoFile        = $selectedCompany->company->company_logo;

        if (!empty($logoFile) && !is_dir(public_path($logoFile))) {
            $companyLogo = public_path($logoFile);
        } else {
            $companyLogo = null;
        }


        $pdf = Pdf::loadView('invoices.pdf', [
            'invoice'          => $invoice,
            'company_name'     => $companyName,
            'company_logo'     => $companyLogo,
            'company_address'  => $selectedCompany->company->address ?? 'N/A',
            'company_phone'    => $selectedCompany->company->phone ?? 'N/A',
            'company_gstin'    => $selectedCompany->company->gstin ?? 'N/A',
            'footer_note'      => 'Thank you for your business',
            'show_signature'   => true,
        ]);

        return $pdf->download('invoice_' . $invoice->invoice_number . '.pdf');
    }



    private function createInvoiceAndPdf(Request $request): array
    {
        $validator = Validator::make($request->all(), [
            'client_name'            => 'required|string',
            'number'                 => 'required|string',
            'email'                  => 'nullable|email',
            'invoice_date'           => 'required|date',

            'discount_price'         => 'nullable|numeric|min:0',
            'discount_percentage'    => 'nullable|numeric|min:0',
            'discount_type'          => 'nullable|in:percentage,amount',

            'payment_method'         => 'required|string',
            'creditPaymentType'      => 'nullable|in:partial,full',
            'partialAmount'          => 'nullable|numeric|min:0',

            'item_type'              => 'nullable|string',
            'delivery_charge'        => 'nullable|numeric|min:0',

            'serviceChargeType'      => 'nullable|in:amount,percentage',
            'serviceChargeAmount'    => 'nullable|numeric|min:0',
            'serviceChargePercent'   => 'nullable|numeric|min:0',

            'address'                => 'nullable|string',
            'pincode'                => 'nullable|string|max:10',

            'items'                  => 'required|array|min:1',
            'items.*.item_id'        => 'required|exists:store_items,id',
            'items.*.variant_id'     => 'nullable|exists:item_variants,id',
            'items.*.quantity'       => 'required|integer|min:1',
            'items.*.unit_price'     => 'nullable|numeric|min:0',
        ]);


        if ($validator->fails()) {
            abort(response()->json([
                'status'  => false,
                'message' => 'Validation failed.',
                'errors'  => $validator->errors(),
            ], 422));
        }

        $data                   = $validator->validated();
        $selectedCompany        = SelectedCompanyService::getSelectedCompanyOrFail();
        $issuedById             = Auth::id();
        $issuedByName           = Auth::user()->name;
        $packageId              = $selectedCompany->company->package_id ?? 1;
        $package                = Package::find($packageId);
        $packageType            = $package['package_type'];
        $allowedInvoiceCount    = $package['invoices_number'];
        $now                    = now();
        $invoiceQuery           = Invoice::where('company_id', $selectedCompany->company_id);

        if ($packageType === 'monthly') {
            $invoiceQuery->whereYear('invoice_date', $now->year)
                ->whereMonth('invoice_date', $now->month);
        } elseif ($packageType === 'yearly') {
            $invoiceQuery->whereYear('invoice_date', $now->year);
        }

        $currentInvoiceCount = $invoiceQuery->count();

        if ($currentInvoiceCount >= $allowedInvoiceCount) {
            throw new \Exception("You have reached your invoice limit for the {$packageType} package ({$allowedInvoiceCount} invoices).");
        }



        $invoice = DB::transaction(function () use ($data, $selectedCompany, $issuedById, $issuedByName) {
            foreach ($data['items'] as $line) {
                $item = Item::findOrFail($line['item_id']);
                if ($item->quantity_count < $line['quantity']) {
                    throw new \Exception("Insufficient stock for '{$item->name}'.");
                }
            }

            $total = collect($data['items'])->sum(function ($line) {
                $item   = Item::findOrFail($line['item_id']);
                $price  = $item->selling_price;

                if (!empty($line['variant_id'])) {
                    $variant = ItemVariant::where('id', $line['variant_id'])
                        ->where('item_id', $item->id)
                        ->firstOrFail();
                    $price = $variant->price;
                }

                $taxRate = DB::table('item_tax')
                    ->join('taxes', 'item_tax.tax_id', '=', 'taxes.id')
                    ->where('item_tax.store_item_id', $item->id)
                    ->value('taxes.rate') ?? 0;

                $priceWithTax = $price + ($price * $taxRate / 100);
                return $priceWithTax * $line['quantity'];
            });
            $serviceChargeAmount    = 0;
            $serviceChargePercent   = 0;
            $serviceChargeGstAmount = 0;
            $finalServiceCharge     = 0;

            if (!empty($data['serviceChargeType'])) {
                if ($data['serviceChargeType'] === 'amount' && !empty($data['serviceChargeAmount'])) {
                    $serviceChargeAmount    = $data['serviceChargeAmount'];
                    $serviceChargePercent   = $total > 0 ? round(($serviceChargeAmount / $total) * 100, 2) : 0;
                } elseif ($data['serviceChargeType'] === 'percentage' && !empty($data['serviceChargePercent'])) {
                    $serviceChargePercent   = $data['serviceChargePercent'];
                    $serviceChargeAmount    = round(($serviceChargePercent / 100) * $total, 2);
                }

                $serviceChargeGstAmount = round($serviceChargeAmount * 0.18, 2);
                $finalServiceCharge     = $serviceChargeAmount + $serviceChargeGstAmount;
            }

            $subtotal = $total + $finalServiceCharge;

            if ($data['discount_type'] === 'amount' && $data['discount_price'] > 0) {

                $discountAmount     = $data['discount_price'];
                $discountPercentage = $subtotal ? round(($discountAmount / $subtotal) * 100, 2) : 0;
                $finalAmount        = round(max(0, $subtotal - $discountAmount));
            } elseif ($data['discount_type'] === 'percentage' && $data['discount_percentage'] > 0) {

                $discountPercentage = $data['discount_percentage'];
                $discountAmount     = round(($discountPercentage / 100) * $subtotal, 2);
                $finalAmount        = round(max(0, $subtotal - $discountAmount));
            } else {
                $discountAmount     = 0;
                $discountPercentage = 0;
                $finalAmount        = round($subtotal);
            }

            $customer = Customer::firstOrCreate(
                ['number'   => $data['number'], 'company_id' => $selectedCompany->company_id],
                ['name'     => $data['client_name'], 'email' => $data['email'] ?? null]
            );


            $companyCode =  $selectedCompany->company->company_id;
            $datePrefix  =  now()->format('Ymd');
            $last        =  Invoice::where('company_id', $selectedCompany->company_id)
                ->whereDate('invoice_date', now()->toDateString())
                ->orderBy('invoice_number', 'desc')
                ->first();


            $nextSeq        = $last ? ((int) substr($last->invoice_number, -4)) + 1 : 1;
            $invoiceNumber  = "{$companyCode}{$datePrefix}" . str_pad($nextSeq, 4, '0', STR_PAD_LEFT);

            $inv = Invoice::create([
                'invoice_number'            => $invoiceNumber,
                'client_name'               => $data['client_name'] ?? 'Guest',
                'client_phone'              => $data['number'] ?? null,
                'client_email'              => $data['email'] ?? null,
                'invoice_date'              => $data['invoice_date'],
                'total_amount'              => $subtotal,
                'sub_total'                 => $total,
                'service_charge_amount'     => $serviceChargeAmount,
                'service_charge_percent'    => 18,
                'service_charge_gst'        => $serviceChargeGstAmount,
                'service_charge_final'      => $finalServiceCharge,
                'discount_amount'           => $discountAmount ?? 0,
                'discount_percentage'       => $discountPercentage ?? 0,
                'final_amount'              => $finalAmount,
                'payment_method'            => $data['payment_method'],
                'issued_by'                 => $issuedById,
                'issued_by_name'            => $issuedByName,
                'company_id'                => $selectedCompany->company_id,
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

                $taxAmount      = $unitPrice * $taxPercentage / 100;
                $totalAmount    = $lineTotal + $taxAmount;

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

            $amountPaid = null;
            $totalDue   = null;
            if ($data['payment_method'] === 'credit') {

                if ($data['creditPaymentType'] === 'partial') {
                    $amountPaid = $data['partialAmount'];
                    $totalDue = $finalAmount - $amountPaid;
                } elseif ($data['creditPaymentType'] === 'full') {
                    $amountPaid = 0;
                    $totalDue = $finalAmount;
                }
            }

            if ($data['payment_method'] === 'credit') {
                CustomerCredit::create([
                    'customer_id'  => $customer->id,
                    'invoice_id'   => $inv->id,
                    'total_due'    => $finalAmount,
                    'amount_paid'  => $amountPaid,
                    'outstanding'  => $totalDue,
                    'company_id'   => $selectedCompany->company_id,
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
            'company_logo'     => $selectedCompany->company->company_logo ?? 'N/A',
            'issued_by'        => Auth::user()->name,
            'footer_note'      => 'Thank you for your business',
            'show_signature'   => true,
        ]);

        return [$invoice, $pdf->output()];
    }


    public function sendToWhatsapp($id)
    {
        $invoice         = Invoice::with('items')->findOrFail($id);
        $selectedCompany = SelectedCompanyService::getSelectedCompanyOrFail();

        if ($invoice->sent_on_whatsapp) {
            return response()->json([
                'status' => false,
                'message' => 'Invoice already sent on WhatsApp.',
            ], 400);
        }

        if (!$invoice->client_phone) {
            return response()->json([
                'status' => false,
                'message' => 'Client phone number not available.',
            ], 400);
        }

        $folderPath = public_path('invoices');
        if (!file_exists($folderPath)) {
            mkdir($folderPath, 0755, true);
        }
        $logoFile = $selectedCompany->company->company_logo;

        if (!empty($logoFile) && !is_dir(public_path($logoFile))) {
            $companyLogo = public_path($logoFile);
        } else {
            $companyLogo = null;
        }

        $pdf = Pdf::loadView('invoices.pdf', [
            'invoice'          => $invoice,
            'company_name'     => $selectedCompany->company->company_name,
            'company_logo'     => $companyLogo,
            'company_address'  => $selectedCompany->company->address ?? 'N/A',
            'company_phone'    => $selectedCompany->company->phone ?? 'N/A',
            'company_gstin'    => $selectedCompany->company->gstin ?? 'N/A',
            'issued_by'        => Auth::user()->name,
            'footer_note'      => 'Thank you for your business',
            'show_signature'   => true,
        ]);

        $fileName = 'invoice_' . $invoice->invoice_number . '.pdf';
        $filePath = $folderPath . '/' . $fileName;
        $pdf->save($filePath);
        $publicUrl = asset('invoices/' . $fileName);

        $payload = [
            "integrated_number" => "918219678757",
            "content_type" => "template",
            "payload" => [
                "messaging_product" => "whatsapp",
                "type" => "template",
                "template" => [
                    "name" => "invoice",
                    "language" => [
                        "code" => "en",
                        "policy" => "deterministic"
                    ],
                    "namespace" => "c448fd19_1766_40ad_b98d_bae2703feb98",
                    "to_and_components" => [
                        [
                            "to" => [$invoice->client_phone],
                            "components" => [
                                "header_1" => [
                                    "filename" => $fileName,
                                    "type"  => "document",
                                    "value" => $publicUrl
                                ],
                                "body_1" => [
                                    "type"  => "text",
                                    "value" => $invoice->client_name ?? 'Customer'
                                ],
                                "body_2" => [
                                    "type"  => "text",
                                    "value" => '₹' . number_format($invoice->total_amount, 2)
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $response = Http::withHeaders([
            'authkey'       => '451198A9qD8Lu26821c9a6P1',
            'Content-Type'  => 'application/json'
        ])->post('https://api.msg91.com/api/v5/whatsapp/whatsapp-outbound-message/bulk/', $payload);

        if ($response->successful()) {
            $invoice->update(['sent_on_whatsapp' => true]);
            return response()->json([
                'status'    => true,
                'message'   => 'WhatsApp message sent successfully.',
                'response'  => $response->json()
            ], 200);
        } else {
            Log::error("MSG91 WhatsApp send failed: " . $response->body());
            return response()->json([
                'status'    => false,
                'message'   => 'Failed to send WhatsApp message.',
                'error'     => $response->body()
            ], $response->status());
        }
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