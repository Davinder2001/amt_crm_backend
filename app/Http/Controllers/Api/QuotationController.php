<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Quotation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Barryvdh\DomPDF\Facade\Pdf;

class QuotationController extends Controller
{


    public function index()
    {
        $quotations = Quotation::get(); 
        return response()->json($quotations);
    }


    // Store quotation in DB
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'customer_name'     => 'required|string',
            'items'             => 'required|array',
            'items.*.name'      => 'required|string',
            'items.*.quantity'  => 'required|numeric',
            'items.*.price'     => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data     = $validator->validated();
        $authUser = $request->user();

        $quotation = Quotation::create([
            'customer_name' => $data['customer_name'],
            'items'         => $data['items'],
            'user_id'       => $authUser ->id,
        ]);

        return response()->json([
            'message'   => 'Quotation saved successfully.',
            'quotation' => $quotation,
        ]);
    }

    // Generate and return PDF (without storing)
    public function generatePdf($id)
    {
        $quotation  = Quotation::findOrFail($id);
        $pdf        = Pdf::loadView('pdf.quotation', compact('quotation'));

        return $pdf->download('quotation_'.$quotation->id.'.pdf');
    }
}
