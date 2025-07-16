<?php

namespace App\Http\Controllers\Api;

use App\Models\Item;
use App\Models\StoreVendor;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Services\SelectedCompanyService;

class StoreVendorController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $selectedCompany    = SelectedCompanyService::getSelectedCompanyOrFail();
        $vendors            = StoreVendor::where('company_id', $selectedCompany->company_id)->get();
        return response()->json($vendors);
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $selectedCompany = SelectedCompanyService::getSelectedCompanyOrFail();

        $validator = Validator::make($request->all(), [
            'vendor_name'       => 'required|string|max:255',
            'vendor_number'     => 'required|string|max:255',
            'vendor_email'      => 'nullable|string|max:255',
            'vendor_address'    => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation errors.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $data               = $validator->validated();
        $data['company_id'] = $selectedCompany->company_id;

        $vendor = StoreVendor::create([
            'company_id'     => $data['company_id'],
            'vendor_name'    => $data['vendor_name'],
            'vendor_number'  => $data['vendor_number'],
            'vendor_email'   => $data['vendor_email'] ?? "NA",
            'vendor_address' => $data['vendor_address'] ?? "NA",

        ]);

        return response()->json([
            'message' => 'Vendor created successfully.',
            'vendor'  => $vendor,
        ], 201);
    }


    /*
    * Display the specified resource.
     */
    public function show($id): JsonResponse
    {
        $selectedCompany = SelectedCompanyService::getSelectedCompanyOrFail();

        // ✅ Fetch vendor with batches (NOT items)
        $vendor = StoreVendor::with(['batches.item' => function ($query) use ($selectedCompany) {
            $query->where('company_id', $selectedCompany->id);
        }])->find($id);

        if (!$vendor) {
            return response()->json(['message' => 'Vendor not found.'], 404);
        }

        if ($vendor->company_id !== $selectedCompany->id) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $groupedByDate = $vendor->batches->groupBy(function ($batch) {
            return ($batch->purchase_date);
        });

        $nestedGrouped = $groupedByDate->map(function ($batchesByDate) {
            return $batchesByDate->groupBy(function ($batch) {
                return $batch->invoice_number ?? 'No Invoice';
            })->map(function ($batchesByInvoice) {
                return $batchesByInvoice->map(function ($batch) {
                    return [
                        'batch_id'        => $batch->id,
                        'item_name'       => $batch->item->name ?? null,
                        'quantity'        => $batch->quantity,
                        'stock'           => $batch->stock,
                        'regular_price'   => $batch->regular_price,
                        'sale_price'      => $batch->sale_price,
                        'cost_price'      => $batch->cost_price,
                        'purchase_date'   => optional($batch->purchase_date)->format('Y-m-d'),
                    ];
                });
            });
        });

        return response()->json([
            'vendor' => [
                'id'            => $vendor->id,
                'name'          => $vendor->vendor_name,
                'number'        => $vendor->vendor_number,
                'email'         => $vendor->vendor_email,
                'address'       => $vendor->vendor_address,
                'items_by_date' => $nestedGrouped,
            ],
        ]);
    }



    /* 
     * Update the specified resource in storage.
    */
    public function update(Request $request, $id): JsonResponse
    {
        $selectedCompany = SelectedCompanyService::getSelectedCompanyOrFail();
        $vendor = StoreVendor::find($id);

        if (!$vendor) {
            return response()->json(['message' => 'Vendor not found.'], 404);
        }

        if ($vendor->company_id !== $selectedCompany->company_id) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'vendor_name'      => 'required|string|max:255',
            'vendor_number'    => 'required|string|max:255',
            'vendor_email'     => 'nullable|string|max:255',
            'vendor_address'   => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation errors.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();

        $vendor->update([
            'vendor_name'    => $data['vendor_name'],
            'vendor_number'  => $data['vendor_number'],
            'vendor_email'   => $data['vendor_email'],
            'vendor_address' => $data['vendor_address'],
        ]);

        return response()->json([
            'message' => 'Vendor updated successfully.',
            'vendor'  => $vendor,
        ]);
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id): JsonResponse
    {
        $selectedCompany    = SelectedCompanyService::getSelectedCompanyOrFail();
        $vendor             = StoreVendor::find($id);

        if (!$vendor) {
            return response()->json([
                'message' => 'Vendor not found.'
            ], 404);
        }

        if ($vendor->company_id !== $selectedCompany->company_id) {
            return response()->json([
                'message' => 'Unauthorized.'
            ], 403);
        }

        $vendor->delete();

        return response()->json([
            'message' => 'Vendor deleted successfully.'
        ]);
    }

    /**
     * Add a company as a vendor.
     */
    public function addAsVendor(Request $request)
    {
        $selectedCompany = SelectedCompanyService::getSelectedCompanyOrFail();
        return response()->json([
            'message'           => 'Cpmpany retrive successfully.',
            'selected company'  => $selectedCompany,
        ], 201);
    }

    /**
     * Get the credit there
     */
    public function vendorCredit($id)
    {
        $selectedCompany = SelectedCompanyService::getSelectedCompanyOrFail();
        $vendor          = StoreVendor::with(['invoices.paymentHistories'])->find($id);

        if (!$vendor) {
            return response()->json(['message' => 'Vendor not found.'], 404);
        }

        if ($vendor->company_id !== $selectedCompany->company_id) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $creditData = [];

        foreach ($vendor->invoices as $invoice) {
            $items = Item::where('vendor_id', $vendor->id)
                ->where('vendor_invoice_id', $invoice->id)
                ->where('company_id', $selectedCompany->company_id)
                ->get();

            $totalInvoiceAmount = $items->sum('cost_price');
            $totalPaid          = $invoice->paymentHistories->sum('amount_paid');
            $totalDue           = $totalInvoiceAmount - $totalPaid;

            $creditData[] = [
                'invoice_id'       => $invoice->id,
                'invoice_no'       => $invoice->invoice_no,
                'invoice_date'     => $invoice->invoice_date,
                'total_amount'     => round($totalInvoiceAmount, 2),
                'total_paid'       => round($totalPaid, 2),
                'total_due'        => round($totalDue, 2),
                'payment_history'  => $invoice->paymentHistories->map(function ($history) {
                    return [
                        'id'                 => $history->id,
                        'payment_method'     => $history->payment_method,
                        'credit_payment_type' => $history->credit_payment_type,
                        'partial_amount'     => $history->partial_amount,
                        'amount_paid'        => $history->amount_paid,
                        'payment_date'       => $history->payment_date,
                        'note'               => $history->note,
                    ];
                }),
            ];
        }

        return response()->json([
            'vendor_id'   => $vendor->id,
            'vendor_name' => $vendor->vendor_name,
            'credit_data' => $creditData,
        ]);
    }
}
