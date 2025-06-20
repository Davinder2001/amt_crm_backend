<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Item;
use App\Models\ItemBatch;
use App\Services\ItemService;
use App\Services\SelectedCompanyService;
use Illuminate\Http\JsonResponse;

class ItemBatchController extends Controller
{
    public function index(): JsonResponse
    {
        $selectedCompany = SelectedCompanyService::getSelectedCompanyOrFail();
        $batches = ItemBatch::with('item')->where('company_id', $selectedCompany->company_id)->get();

        return response()->json($batches);
    }

    public function show($id): JsonResponse
    {
        $selectedCompany = SelectedCompanyService::getSelectedCompanyOrFail();
        $batch = ItemBatch::with('item')
            ->where('company_id', $selectedCompany->company_id)
            ->findOrFail($id);

        return response()->json($batch);
    }

    public function store(Request $request): JsonResponse
    {
        $selectedCompany = SelectedCompanyService::getSelectedCompanyOrFail();
        $companyId = $selectedCompany->company_id;

        $data = $request->validate([
            'item_id'        => 'required|exists:items,id',
            'batch_number'   => 'nullable|string|max:255',
            'cost_price'     => 'required|numeric|min:0',
            'quantity_count' => 'required|integer|min:1',
        ]);

        $item = Item::where('company_id', $companyId)->findOrFail($data['item_id']);

        ItemService::createBatch($item, array_merge($data, [
            'company_id' => $companyId,
        ]));

        return response()->json(['success' => true, 'message' => 'Batch created successfully.'], 201);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $selectedCompany = SelectedCompanyService::getSelectedCompanyOrFail();
        $companyId = $selectedCompany->company_id;

        $batch = ItemBatch::where('company_id', $companyId)->findOrFail($id);
        $item = Item::where('company_id', $companyId)->findOrFail($batch->item_id);

        $data = $request->validate([
            'cost_price'     => 'nullable|numeric|min:0',
            'quantity_count' => 'nullable|integer|min:0',
        ]);

        ItemService::updateBatch($item, $data);

        return response()->json(['success' => true, 'message' => 'Batch updated successfully.']);
    }

    public function destroy($id): JsonResponse
    {
        $selectedCompany = SelectedCompanyService::getSelectedCompanyOrFail();
        $batch = ItemBatch::where('company_id', $selectedCompany->company_id)->findOrFail($id);
        $batch->delete();

        return response()->json(['success' => true, 'message' => 'Batch deleted successfully.']);
    }
}
