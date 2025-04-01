<?php

namespace App\Http\Controllers\Api;

use App\Models\Item;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Services\SelectedCompanyService;

class ItemsController extends Controller
{
    // List all items
    public function index(): JsonResponse
    {
        $selectedCompany = SelectedCompanyService::getSelectedCompanyOrFail();

        if (!$selectedCompany->super_admin) {
            $items = Item::where('company_id', $selectedCompany->company_id)->get();
        } else {
            $items = Item::all();
        }

        return response()->json($items);
    }

    // Add a new item
    public function store(Request $request): JsonResponse
    {
        $selectedCompany = SelectedCompanyService::getSelectedCompanyOrFail();

        $validator = Validator::make($request->all(), [
            'name'                => 'required|string|max:255',
            'quantity_count'      => 'required|integer',
            'measurement'         => 'nullable|string',
            'purchase_date'       => 'nullable|date',
            'date_of_manufacture' => 'required|date',
            'date_of_expiry'      => 'nullable|date',
            'brand_name'          => 'required|string|max:255',
            'replacement'         => 'nullable|string|max:255',
            'category'            => 'nullable|string|max:255',
            'vendor_name'         => 'nullable|string|max:255',
            'availability_stock'  => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation errors.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();
        $data['company_id'] = $selectedCompany->company_id;

        $item = Item::create($data);

        return response()->json([
            'message' => 'Item added successfully.',
            'item'    => $item,
        ], 201);
    }

    // Get a specific item by ID
    public function show($id): JsonResponse
    {
        $selectedCompany = SelectedCompanyService::getSelectedCompanyOrFail();
        $item = Item::find($id);

        if (!$item) {
            return response()->json(['message' => 'Item not found.'], 404);
        }

        if (!$selectedCompany->super_admin && $item->company_id !== $selectedCompany->company_id) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        return response()->json($item);
    }

    // Update an existing item
    public function update(Request $request, $id): JsonResponse
    {
        $selectedCompany = SelectedCompanyService::getSelectedCompanyOrFail();
        $item = Item::find($id);

        if (!$item) {
            return response()->json(['message' => 'Item not found.'], 404);
        }

        if (!$selectedCompany->super_admin && $item->company_id !== $selectedCompany->company_id) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'name'                => 'sometimes|required|string|max:255',
            'quantity_count'      => 'sometimes|required|integer',
            'measurement'         => 'nullable|string',
            'purchase_date'       => 'nullable|date',
            'date_of_manufacture' => 'sometimes|required|date',
            'date_of_expiry'      => 'nullable|date',
            'brand_name'          => 'sometimes|required|string|max:255',
            'replacement'         => 'nullable|string|max:255',
            'category'            => 'nullable|string|max:255',
            'vendor_name'         => 'nullable|string|max:255',
            'availability_stock'  => 'sometimes|required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation errors.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $item->update($validator->validated());

        return response()->json([
            'message' => 'Item updated successfully.',
            'item'    => $item,
        ]);
    }

    public function destroy($id): JsonResponse
    {
        $selectedCompany = SelectedCompanyService::getSelectedCompanyOrFail();
        $item = Item::find($id);

        if (!$item) {
            return response()->json(['message' => 'Item not found.'], 404);
        }

        if (!$selectedCompany->super_admin && $item->company_id !== $selectedCompany->company_id) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $item->delete();

        return response()->json(['message' => 'Item deleted successfully.']);
    }
}
