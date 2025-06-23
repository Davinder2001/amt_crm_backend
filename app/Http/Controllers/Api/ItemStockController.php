<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Item;
use App\Models\ItemBatch;
use Illuminate\Support\Facades\Validator;
use App\Services\SelectedCompanyService;

class ItemStockController extends Controller
{
    /**
     * List all item batches for selected company
     */
    public function index()
    {
        $companyId = SelectedCompanyService::getSelectedCompanyOrFail()->company->id;

        $batches = ItemBatch::with('item')
            ->where('company_id', $companyId)
            ->latest()
            ->get();

        return response()->json([
            'status' => true,
            'batches' => $batches,
        ]);
    }

    /**
     * Create a new item batch stock
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'item_id'       => 'required|exists:items,id',
            'batch_number'  => 'nullable|string|max:255',
            'purchase_price'=> 'required|numeric|min:0',
            'quantity'      => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
        }

        $companyId = SelectedCompanyService::getSelectedCompanyOrFail()->company->id;

        $batch = ItemBatch::create([
            'company_id'     => $companyId,
            'item_id'        => $request->item_id,
            'batch_number'   => $request->batch_number ?? 'BATCH-' . strtoupper(uniqid()),
            'purchase_price' => $request->purchase_price,
            'quantity'       => $request->quantity,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Item batch stock created successfully.',
            'batch' => $batch,
        ]);
    }

    /**
     * Show a single batch
     */
    public function show($id)
    {
        $companyId = SelectedCompanyService::getSelectedCompanyOrFail()->company->id;

        $batch = ItemBatch::with('item')
            ->where('company_id', $companyId)
            ->where('id', $id)
            ->first();

        if (!$batch) {
            return response()->json(['status' => false, 'message' => 'Batch not found.'], 404);
        }

        return response()->json(['status' => true, 'batch' => $batch]);
    }

    /**
     * Update a batch
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'batch_number'   => 'nullable|string|max:255',
            'purchase_price' => 'required|numeric|min:0',
            'quantity'       => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
        }

        $companyId = SelectedCompanyService::getSelectedCompanyOrFail()->company->id;

        $batch = ItemBatch::where('company_id', $companyId)->where('id', $id)->first();

        if (!$batch) {
            return response()->json(['status' => false, 'message' => 'Batch not found.'], 404);
        }

        $batch->update([
            'batch_number'   => $request->batch_number ?? $batch->batch_number,
            'purchase_price' => $request->purchase_price,
            'quantity'       => $request->quantity,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Item batch stock updated successfully.',
            'batch' => $batch,
        ]);
    }

    /**
     * Delete a batch
     */
    public function destroy($id)
    {
        $companyId = SelectedCompanyService::getSelectedCompanyOrFail()->company->id;

        $batch = ItemBatch::where('company_id', $companyId)->where('id', $id)->first();

        if (!$batch) {
            return response()->json(['status' => false, 'message' => 'Batch not found.'], 404);
        }

        $batch->delete();

        return response()->json([
            'status' => true,
            'message' => 'Item batch stock deleted successfully.',
        ]);
    }
}
