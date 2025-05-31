<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Quotation;
use Illuminate\Http\Request;
use App\Services\SelectedCompanyService;
use Illuminate\Support\Facades\Validator;
use Barryvdh\DomPDF\Facade\Pdf;

class QuotationController extends Controller
{
    public function index()
    {
        $quotations = Quotation::get();
        return response()->json($quotations);
    }


    public function show($id)
    {
        $quotation = Quotation::find($id);

        if (!$quotation) {
            return response()->json(['message' => 'Quotation not found.'], 404);
        }

        return response()->json($quotation);
    }



    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'customer_name'     => 'required|string|max:255',
            'customer_number'   => 'required|string|max:20',
            'customer_email'    => 'nullable|email|max:255',
            'items'             => 'required|array|min:1',
            'items.*.name'      => 'required|string|max:255',
            'items.*.quantity'  => 'required|numeric|min:1',
            'items.*.price'     => 'required|numeric|min:0',
            'tax_percent'       => 'nullable|numeric|min:0',
            'service_charges'   => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $company    = SelectedCompanyService::getSelectedCompanyOrFail();
        $data       = $validator->validated();
        $user       = $request->user();
        $subtotal   = 0;

        foreach ($data['items'] as $item) {
            $subtotal += $item['quantity'] * $item['price'];
        }

        $taxPercent     = $data['tax_percent'] ?? 0;
        $taxAmount      = ($subtotal * $taxPercent) / 100;
        $serviceCharges = $data['service_charges'] ?? 0;
        $total          = $subtotal + $taxAmount + $serviceCharges;

        $quotation = Quotation::create([
            'user_id'          => $user->id,
            'company_id'       => $company->company->id,
            'company_name'     => $company->company->company_name,
            'customer_name'    => $data['customer_name'],
            'customer_number'  => $data['customer_number'],
            'customer_email'   => $data['customer_email'] ?? null,
            'items'            => $data['items'],
            'tax_percent'      => $taxPercent,
            'tax_amount'       => $taxAmount,
            'service_charges'  => $serviceCharges,
            'total'            => $total,
        ]);

        return response()->json([
            'message'   => 'Quotation saved successfully.',
            'quotation' => $quotation,
        ]);
    }

    public function generatePdf($id)
    {
        $quotation  = Quotation::findOrFail($id);
        $pdf        = Pdf::loadView('pdf.quotation', compact('quotation'));

        return $pdf->download('quotation_' . $quotation->id . '.pdf');
    }
}
