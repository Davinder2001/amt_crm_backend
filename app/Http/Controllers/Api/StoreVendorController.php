<?php

namespace App\Http\Controllers\Api;

use App\Models\StoreVendor;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Services\SelectedCompanyService;

class StoreVendorController extends Controller
{
    // List all vendors for the selected company.
    public function index(): JsonResponse
    {
        $selectedCompany = SelectedCompanyService::getSelectedCompanyOrFail();

        $vendors = StoreVendor::where('company_id', $selectedCompany->company_id)->get();

        return response()->json($vendors);
    }

    // Create a new vendor.
    public function store(Request $request): JsonResponse
    {
        $selectedCompany = SelectedCompanyService::getSelectedCompanyOrFail();

        $validator = Validator::make($request->all(), [
            'vendor_name' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation errors.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();
        $data['company_id'] = $selectedCompany->company_id;

        $vendor = StoreVendor::create($data);

        return response()->json([
            'message' => 'Vendor created successfully.',
            'vendor'  => $vendor,
        ], 201);
    }

    // Get a specific vendor by ID.
    public function show($id): JsonResponse
    {
        $selectedCompany = SelectedCompanyService::getSelectedCompanyOrFail();
        $vendor = StoreVendor::find($id);

        if (!$vendor) {
            return response()->json(['message' => 'Vendor not found.'], 404);
        }

        // Ensure the vendor belongs to the user's company.
        if ($vendor->company_id !== $selectedCompany->company_id) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        return response()->json($vendor);
    }

    // Update an existing vendor.
    public function update(Request $request, $id): JsonResponse
    {
        $selectedCompany = SelectedCompanyService::getSelectedCompanyOrFail();
        $vendor = StoreVendor::find($id);

        if (!$vendor) {
            return response()->json(['message' => 'Vendor not found.'], 404);
        }

        // Ensure the vendor belongs to the user's company.
        if ($vendor->company_id !== $selectedCompany->company_id) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'vendor_name' => 'sometimes|required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation errors.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $vendor->update($validator->validated());

        return response()->json([
            'message' => 'Vendor updated successfully.',
            'vendor'  => $vendor,
        ]);
    }

    // Delete a vendor.
    public function destroy($id): JsonResponse
    {
        $selectedCompany = SelectedCompanyService::getSelectedCompanyOrFail();
        $vendor = StoreVendor::find($id);

        if (!$vendor) {
            return response()->json(['message' => 'Vendor not found.'], 404);
        }

        if ($vendor->company_id !== $selectedCompany->company_id) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $vendor->delete();

        return response()->json(['message' => 'Vendor deleted successfully.']);
    }

     
    
    public function addAsVendor(Request $request){
        $selectedCompany = SelectedCompanyService::getSelectedCompanyOrFail();

        

    }
}
