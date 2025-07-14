<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Item;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use App\Services\InvoiceServices\InvoiceHelperService;
use App\Models\CompanyAccount;
use App\Models\ItemBatch;
use App\Models\ItemVariant;
use App\Models\InvoiceItem;
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
        $invoices   = Invoice::with(['items', 'credit'])->latest()->get();

        return response()->json([
            'status'   => true,
            'invoices' => $invoices,
        ]);
    }

    /**
     * Store a newly created invoice in storage.
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
     * Create an invoice and generate a PDF.
     *
     */
    private function createInvoiceAndPdf(Request $request): array
    {
        $validator = Validator::make($request->all(), [
            'client_name'           => 'required|string',
            'number'                => 'required|string',
            'email'                 => 'nullable|email',
            'invoice_date'          => 'required|date',
            'payment_method'        => 'required|string',
            'creditPaymentType'     => 'nullable|in:partial,full',
            'partialAmount'         => 'nullable|numeric|min:0',
            'credit_note'           => 'nullable|string',
            'bank_account_id'       => 'nullable|integer|exists:company_accounts,id',
            'item_type'             => 'nullable|string',
            'discount_price'        => 'nullable|numeric|min:0',
            'discount_percentage'   => 'nullable|numeric|min:0',
            'discount_type'         => 'nullable|in:percentage,amount',

            'delivery_charge'       => 'nullable|numeric|min:0',
            'delivery_boy'          => 'nullable|exists:users,id',

            'serviceChargeType'     => 'nullable|in:amount,percentage',
            'serviceChargeAmount'   => 'nullable|numeric|min:0',
            'serviceChargePercent'  => 'nullable|numeric|min:0',
            'address'               => 'nullable|string',
            'pincode'               => 'nullable|string|max:10',

            'items'                 => 'required|array|min:1',
            'items.*.item_id'       => 'required|exists:store_items,id',
            'items.*.sale_by'       => 'required|in:piece,unit',
            'items.*.variant_id'    => 'nullable|exists:item_variants,id',
            'items.*.quantity'      => 'required|numeric|min:0.01',
            'items.*.batch_id'      => 'required|integer|min:1',
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
        $issuedByName    = Auth::user()->name;


        $unitPriceFor = static function (int $itemId, int $batchId, ?int $variantId = null, string $saleBy = 'piece'): float {
            $batch = ItemBatch::where('id', $batchId)->where('item_id', $itemId)->firstOrFail();
            if ($variantId) {
                $variant = ItemVariant::where('id', $variantId)->where('batch_id', $batch->id)->first();
                if ($variant) {
                    return $saleBy === 'unit' ? $variant->variant_price_per_unit : $variant->variant_sale_price;
                }
            }
            return $saleBy === 'unit'
                ? ($batch->price_per_unit ?? $batch->regular_price ?? 0.0)
                : ($batch->sale_price ?? $batch->regular_price ?? 0.0);
        };

        $invoice = DB::transaction(function () use ($data, $selectedCompany, $issuedById, $issuedByName, $unitPriceFor) {
            $total = collect($data['items'])->sum(function ($row) use ($unitPriceFor) {
                $price = $unitPriceFor($row['item_id'], $row['batch_id'], $row['variant_id'] ?? null, $row['sale_by']);
                $taxRate = DB::table('item_tax')
                    ->join('taxes', 'item_tax.tax_id', '=', 'taxes.id')
                    ->where('item_tax.store_item_id', $row['item_id'])
                    ->value('taxes.rate') ?? 0;
                return ($price + ($price * $taxRate / 100)) * $row['quantity'];
            });

            $serviceChargeAmount = $serviceChargePercent = $serviceChargeGstAmount = $finalServiceCharge = 0;
            if (!empty($data['serviceChargeType'])) {
                if ($data['serviceChargeType'] === 'amount' && !empty($data['serviceChargeAmount'])) {
                    $serviceChargeAmount  = $data['serviceChargeAmount'];
                    $serviceChargePercent = $total > 0 ? round(($serviceChargeAmount / $total) * 100, 2) : 0;
                } elseif ($data['serviceChargeType'] === 'percentage' && !empty($data['serviceChargePercent'])) {
                    $serviceChargePercent = $data['serviceChargePercent'];
                    $serviceChargeAmount  = round(($serviceChargePercent / 100) * $total, 2);
                }
                $serviceChargeGstAmount = round($serviceChargeAmount * 0.18, 2);
                $finalServiceCharge = $serviceChargeAmount + $serviceChargeGstAmount;
            }

            $subtotal       = $total + $finalServiceCharge;
            $discountAmount = $discountPercentage = 0;
            $finalAmount    = round($subtotal);

            if ($data['discount_type'] === 'amount' && $data['discount_price'] > 0) {
                $discountAmount = $data['discount_price'];
                $discountPercentage = $subtotal > 0 ? round(($discountAmount / $subtotal) * 100, 2) : 0;
                $finalAmount = round(max(0, $subtotal - $discountAmount));
            } elseif ($data['discount_type'] === 'percentage' && $data['discount_percentage'] > 0) {
                $discountPercentage = $data['discount_percentage'];
                $discountAmount = round(($discountPercentage / 100) * $subtotal, 2);
                $finalAmount = round(max(0, $subtotal - $discountAmount));
            }

            if (isset($data['delivery_charge']) && is_numeric($data['delivery_charge'])) {
                $finalAmount += $data['delivery_charge'];
            }

            $customer = InvoiceHelperService::createCustomer($data, $selectedCompany->company_id);
            $today = now()->toDateString();
            $shortDate = now()->format('ymd');
            $companyCode = 'C' . $selectedCompany->company->id;
            $lastInv = Invoice::where('company_id', $selectedCompany->company->id)->whereDate('invoice_date', $today)->orderBy('invoice_number', 'desc')->first();
            $nextSeq = $lastInv ? ((int) substr($lastInv->invoice_number, -3)) + 1 : 1;
            $invoiceNo = $companyCode . $shortDate . str_pad($nextSeq, 3, '0', STR_PAD_LEFT);

            $inv = Invoice::create([
                'invoice_number'          => $invoiceNo,
                'client_name'             => $data['client_name'] ?? 'Guest',
                'client_phone'            => $data['number'],
                'client_email'            => $data['email'] ?? null,
                'invoice_date'            => $data['invoice_date'],
                'total_amount'            => $subtotal,
                'sub_total'               => $total,
                'service_charge_amount'   => $serviceChargeAmount,
                'service_charge_percent'  => 18,
                'service_charge_gst'      => $serviceChargeGstAmount,
                'service_charge_final'    => $finalServiceCharge,
                'discount_amount'         => $discountAmount,
                'discount_percentage'     => $discountPercentage,
                'delivery_charge'         => $data['delivery_charge'] ?? 0,
                'delivery_address'        => $data['address'] ?? null,
                'delivery_pincode'        => $data['pincode'] ?? null,
                'final_amount'            => $finalAmount,
                'payment_method'          => $data['payment_method'],
                'bank_account_id'         => $data['bank_account_id'] ?? null,
                'credit_note'             => $data['credit_note'] ?? null,
                'delivery_boy'            => $data['delivery_boy'] ?? null,
                'issued_by'               => $issuedById,
                'issued_by_name'          => $issuedByName,
                'company_id'              => $selectedCompany->company_id,
            ]);

            $historyItems = [];

            foreach ($data['items'] as $row) {
                $item = Item::findOrFail($row['item_id']);
                $unitPrice = $unitPriceFor($item->id, $row['batch_id'], $row['variant_id'] ?? null, $row['sale_by']);
                $taxPercentage = DB::table('item_tax')->join('taxes', 'item_tax.tax_id', '=', 'taxes.id')->where('item_tax.store_item_id', $item->id)->value('taxes.rate') ?? 0;
                $taxAmount = $unitPrice * $taxPercentage / 100;
                $lineTotal = $unitPrice * $row['quantity'];
                $totalAmount = $lineTotal + $taxAmount;

                InvoiceItem::create([
                    'invoice_id'     => $inv->id,
                    'company_id'     => $selectedCompany->company->id,
                    'item_id'        => $item->id,
                    'variant_id'     => $row['variant_id'] ?? null,
                    'description'    => $item->name,
                    'quantity'       => $row['quantity'],
                    'sale_by'        => $row['sale_by'],
                    'unit_price'     => $unitPrice,
                    'tax_percentage' => $taxPercentage,
                    'tax_amount'     => $taxAmount,
                    'total'          => $totalAmount,
                ]);


                $qty = (float) $row['quantity'];
                $batch = ItemBatch::find($row['batch_id']);
                if ($batch) {
                    $batch->stock = max(0, $batch->stock - $qty);
                    $batch->save();
                }

                if (!empty($row['variant_id'])) {
                    $variant = ItemVariant::find($row['variant_id']);
                    if ($variant) {
                        $variant->stock = max(0, $variant->stock - $qty);
                        $variant->save();
                    }
                }

                $historyItems[] = [
                    'description' => $item->name,
                    'quantity'    => $row['quantity'],
                    'unit_price'  => $unitPrice,
                    'total'       => $lineTotal,
                ];
            }

            InvoiceHelperService::createCustomerHistory($customer, $historyItems, $inv->id, $data['invoice_date'], $inv->invoice_number, $total);

            if ($data['payment_method'] === 'credit') {
                InvoiceHelperService::createCreditHistory($customer, $data, $finalAmount, $inv->id, $selectedCompany->company_id);
            }

            if (!empty($data['delivery_boy'])) {
                InvoiceHelperService::createDeliveryTask($inv, $data['delivery_boy']);
            }


            return $inv;
        });

        $invoice->load(['items.variant', 'credit']);

        if (!empty($data['email'])) {
            InvoiceHelperService::sendInvoiceEmail($data['email'], $invoice, $selectedCompany->company);
        }

        return [$invoice];
    }


    /**
     * Store a newly created invoice and return the PDF content.
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
     * Store a newly created invoice and send it via email.
     */
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

    /**
     * Download the invoice as a PDF.
     */
    public function download($id)
    {
        $invoice         = Invoice::with('items')->findOrFail($id);
        $selectedCompany = SelectedCompanyService::getSelectedCompanyOrFail();
        $company         = $selectedCompany->company;

        $companyName     = $company->company_name;
        $logoFile        = $company->company_logo;

        $companyLogo = (!empty($logoFile) && !is_dir(public_path($logoFile)))
            ? public_path($logoFile)
            : null;

        $invoiceID = $invoice->id;
        $customerCredits = CustomerCredit::where('invoice_id', $invoiceID)->get();

        // ✅ Fetch delivery boy by ID from the invoice
        $deliveryBoyName = null;
        if ($invoice->delivery_boy) {
            $deliveryBoy = User::find($invoice->delivery_boy);
            $deliveryBoyName = $deliveryBoy?->name ?? null;
        }

        $pdf = Pdf::loadView('invoices.pdf', [
            'invoice'           => $invoice,
            'company_name'      => $companyName,
            'customer_credits'  => $customerCredits,
            'signature'         => $company->company_signature,
            'company_logo'      => $companyLogo,
            'company_address'   => $company->address ?? 'N/A',
            'company_phone'     => $company->phone ?? 'N/A',
            'company_gstin'     => $company->gstin ?? 'N/A',
            'footer_note'       => 'Thank you for your business',
            'show_signature'    => false,
            'delivery_boy_name' => $deliveryBoyName,
        ]);

        return $pdf->download('invoice_' . $invoice->invoice_number . '.pdf');
    }


    /**
     * Update the specified invoice in storage.
     */
    public function update(Request $request, $id)
    {
        $invoice = Invoice::with('items')->findOrFail($id);
        return response([$invoice]);
    }

    /**
     * Send the invoice to the client via WhatsApp.
     */
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
                                    "value" => '₹' . number_format($invoice->final_amount, 2)
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

    /**
     * Display the specified invoice.
     */
    public function show($id)
    {
        $invoice = Invoice::with('items', 'credit')->findOrFail($id);

        return response()->json([
            'status'  => true,
            'invoice' => $invoice,
        ]);
    }


    /**
     * Display the online payment history.
     */
    public function onlinePaymentHistory()
    {
        $selectedCompany = SelectedCompanyService::getSelectedCompanyOrFail();
        $invoices = Invoice::where('company_id', $selectedCompany->company_id)->where('payment_method', 'online')->get();

        $grouped = $invoices->groupBy('bank_account_id')->map(function ($invoiceGroup, $bankAccountId) {
            $bankAccount = CompanyAccount::find($bankAccountId);

            return [
                'bank_account_id'   => $bankAccountId,
                'bank_name'         => $bankAccount->bank_name ?? 'N/A',
                'account_number'    => $bankAccount->account_number ?? 'N/A',
                'ifsc_code'         => $bankAccount->ifsc_code ?? 'N/A',
                'total_transferred' => $invoiceGroup->sum('final_amount'),
                'transactions'      => $invoiceGroup->map(function ($inv) {
                    return [
                        'invoice_number' => $inv->invoice_number,
                        'invoice_date'   => $inv->invoice_date,
                        'amount'         => $inv->final_amount,
                    ];
                })->values()
            ];
        })->values();

        return response()->json([
            'status' => true,
            'data' => $grouped,
        ]);
    }


    /**
     * Display the cash payment history.
     */
    public function cashPaymentHistory()
    {
        $selectedCompany = SelectedCompanyService::getSelectedCompanyOrFail();
        $invoices        = Invoice::where('company_id', $selectedCompany->company_id)->where('payment_method', 'cash')->get();

        $grouped = $invoices->groupBy('invoice_date')->map(function ($dateGroup, $date) {
            return [
                'date' => $date,
                'total' => $dateGroup->sum('final_amount'),
                'transactions' => $dateGroup->map(function ($inv) {
                    return [
                        'invoice_number' => $inv->invoice_number,
                        'amount' => $inv->final_amount,
                    ];
                })->values()
            ];
        })->values();

        return response()->json([
            'status' => true,
            'payment_method' => 'cash',
            'data' => $grouped
        ]);
    }

    /**
     * Display the card payment history.
     */
    public function cardPaymentHistory()
    {
        $selectedCompany = SelectedCompanyService::getSelectedCompanyOrFail();
        $invoices = Invoice::where('company_id', $selectedCompany->company_id)->where('payment_method', 'card')->get();

        $grouped = $invoices->groupBy('invoice_date')->map(function ($dateGroup, $date) {
            return [
                'date' => $date,
                'total' => $dateGroup->sum('final_amount'),
                'transactions' => $dateGroup->map(function ($inv) {
                    return [
                        'invoice_number' => $inv->invoice_number,
                        'amount' => $inv->final_amount,
                    ];
                })->values()
            ];
        })->values();

        return response()->json([
            'status' => true,
            'payment_method' => 'card',
            'data' => $grouped
        ]);
    }

    /**
     * Display the credit payment history.
     */
    public function creditPaymentHistory()
    {
        $selectedCompany = SelectedCompanyService::getSelectedCompanyOrFail();
        $invoices = Invoice::where('company_id', $selectedCompany->company_id)->where('payment_method', 'credit')->get();

        $grouped = $invoices->groupBy('invoice_date')->map(function ($dateGroup, $date) {
            return [
                'date' => $date,
                'total' => $dateGroup->sum('final_amount'),
                'transactions' => $dateGroup->map(function ($inv) {
                    return [
                        'invoice_number' => $inv->invoice_number,
                        'amount'         => $inv->final_amount,
                    ];
                })->values()
            ];
        })->values();

        return response()->json([
            'status'         => true,
            'payment_method' => 'credit',
            'data'           => $grouped
        ]);
    }

    /**
     * Display the self-consumption payment history.
     */
    public function selfConsumptionHistory()
    {
        $selectedCompany = SelectedCompanyService::getSelectedCompanyOrFail();
        $invoices        = Invoice::where('company_id', $selectedCompany->company_id)->where('payment_method', 'self')->get();

        $grouped = $invoices->groupBy('invoice_date')->map(function ($dateGroup, $date) {
            return [
                'date'  => $date,
                'total' => $dateGroup->sum('final_amount'),
                'transactions' => $dateGroup->map(function ($inv) {
                    return [
                        'invoice_number' => $inv->invoice_number,
                        'amount'         => $inv->final_amount,
                    ];
                })->values()
            ];
        })->values();

        return response()->json([
            'status'         => true,
            'payment_method' => 'self consumption',
            'data'           => $grouped
        ]);
    }

    /**
     * Store a newly created invoice and send it via WhatsApp.
     */
    public function storeAndSendWhatsapp(Request $request)
    {
        [$invoice, $pdfContent] = $this->createInvoiceAndPdf($request);

        $selectedCompany = SelectedCompanyService::getSelectedCompanyOrFail();
        $logoFile        = $selectedCompany->company->company_logo;

        $folderPath = public_path('invoices');
        if (!file_exists($folderPath)) {
            mkdir($folderPath, 0755, true);
        }

        $companyLogo = (!empty($logoFile) && !is_dir(public_path($logoFile))) ? public_path($logoFile) : null;

        $fileName = 'invoice_' . $invoice->invoice_number . '.pdf';
        $filePath = $folderPath . '/' . $fileName;
        file_put_contents($filePath, $pdfContent);
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
                                    "value" => '₹' . number_format($invoice->final_amount, 2)
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
                'status'  => true,
                'message' => 'Invoice created and WhatsApp message sent.',
                'invoice' => $invoice,
            ], 201);
        } else {
            return response()->json([
                'status'  => false,
                'message' => 'Invoice saved but WhatsApp sending failed.',
                'invoice' => $invoice,
                'error'   => $response->body()
            ], 500);
        }
    }
}
