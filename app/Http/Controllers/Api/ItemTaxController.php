<?php

namespace App\Http\Controllers\Api;

use App\Models\Tax;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Services\SelectedCompanyService;
use App\Http\Resources\TaxResource;

class ItemTaxController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $selectedCompany    = SelectedCompanyService::getSelectedCompanyOrFail();
        $taxes              = Tax::where('company_id', $selectedCompany->id)->get();

        return response()->json([
            'success' => true,
            'data' => TaxResource::collection($taxes)
        ]);
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'rate' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success'   => false,
                'message'   => 'Validation errors',
                'errors'    => $validator->errors()
            ], 422);
        }

        $selectedCompany = SelectedCompanyService::getSelectedCompanyOrFail();

        $tax = Tax::create([
            'company_id'    => $selectedCompany->id,
            'name'          => $request->name,
            'rate'          => $request->rate,
        ]);

        return response()->json([
            'success'   => true,
            'message'   => 'Tax created successfully',
            'data'      => new TaxResource($tax)
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Tax $tax)
    {
        return response()->json([
            'success' => true,
            'data' => new TaxResource($tax)
        ]);
    }


    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Tax $tax)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'rate' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        $tax->update($validator->validated());

        return response()->json([
            'success' => true,
            'message' => 'Tax updated successfully',
            'data' => new TaxResource($tax)
        ]);
    }
    

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Tax $tax)
    {
        $tax->delete();

        return response()->json([
            'success' => true,
            'message' => 'Tax deleted successfully'
        ]);
    }
}
