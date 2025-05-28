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

        $vendor = StoreVendor::with(['items' => function ($query) use ($selectedCompany) {
            $query->where('company_id', $selectedCompany->company_id);
        }])->find($id);

        if (!$vendor) {
            return response()->json(['message' => 'Vendor not found.'], 404);
        }

        if ($vendor->company_id !== $selectedCompany->company_id) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        // Step 1: Group items by purchase_date
        $groupedByDate = $vendor->items->groupBy(function ($item) {
            return optional($item->purchase_date)->format('Y-m-d') ?? 'Unknown Date';
        });

        // Step 2: Within each date group, group by invoice_no
        $nestedGrouped = $groupedByDate->map(function ($itemsByDate) {
            return $itemsByDate->groupBy(function ($item) {
                return $item->invoice_no ?? 'No Invoice';
            });
        });

        return response()->json([
            'vendor' => [
                'id'      => $vendor->id,
                'name'    => $vendor->vendor_name,
                'number'  => $vendor->vendor_number,
                'email'   => $vendor->vendor_email,
                'address' => $vendor->vendor_address,
                'items_by_date' => $nestedGrouped,
            ],
        ]);
    }



    /* /
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id): JsonResponse
    {
        $selectedCompany    = SelectedCompanyService::getSelectedCompanyOrFail();
        $vendor             = StoreVendor::find($id);

        if (!$vendor) {
            return response()->json(['message' => 'Vendor not found.'], 404);
        }

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
}
