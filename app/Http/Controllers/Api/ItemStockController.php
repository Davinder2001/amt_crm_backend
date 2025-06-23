<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ItemBatch;
use Illuminate\Support\Facades\Validator;
use App\Services\SelectedCompanyService;
use App\Http\Resources\BatchResource;

class ItemStockController extends Controller
{
    public function index()
    {
        $batches = ItemBatch::with(['item', 'variants.attributeValues.attribute', 'unit'])->latest()->get();

        return response()->json([
            'status'  => true,
            'batches' => BatchResource::collection($batches),
        ], 200);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'item_id'            => 'required|exists:items,id',
            'batch_number'       => 'nullable|string|max:255',
            'purchase_price'     => 'required|numeric|min:0',
            'quantity'           => 'required|numeric|min:0',
            'product_type'       => 'nullable|string|max:50',
            'unit_of_measure'    => 'nullable|string|max:50',


            'variant_ids'        => 'nullable|array',
            'variant_ids.*'      => 'integer|exists:item_variants,id',

            'quantity_count'     => 'nullable|numeric|min:0',
            'purchase_date'      => 'nullable|date',
            'date_of_manufacture' => 'nullable|date',
            'date_of_expiry'     => 'nullable|date',
            'brand_name'         => 'nullable|string|max:255',
            'brand_id'           => 'nullable|integer',
            'replacement'        => 'nullable|numeric|min:0',
            'cost_price'         => 'nullable|numeric|min:0',
            'regular_price'      => 'nullable|numeric|min:0',
            'sale_price'         => 'nullable|numeric|min:0',
            'unit_id'            => 'nullable|exists:measuring_units,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
        }

        $companyId = SelectedCompanyService::getSelectedCompanyOrFail()->company->id;

        $batch = ItemBatch::create([
            'company_id'          => $companyId,
            'item_id'             => $request->item_id,
            'batch_number'        => $request->batch_number ?? 'BATCH-' . strtoupper(uniqid()),
            'purchase_price'      => $request->purchase_price,
            'quantity'            => $request->quantity,
            'quantity_count'      => $request->quantity_count,
            'purchase_date'       => $request->purchase_date,
            'date_of_manufacture' => $request->date_of_manufacture,
            'date_of_expiry'      => $request->date_of_expiry,
            'brand_name'          => $request->brand_name,
            'brand_id'            => $request->brand_id,
            'replacement'         => $request->replacement,
            'cost_price'          => $request->cost_price,
            'regular_price'       => $request->regular_price,
            'sale_price'          => $request->sale_price,
            'product_type'        => $request->product_type,
            'unit_of_measure'     => $request->unit_of_measure,
            'unit_id'             => $request->unit_id,
        ]);

        if ($request->filled('variant_ids')) {
            $batch->variants()->sync($request->variant_ids);
        }

        if ($request->filled('categories')) {
            $batch->categories()->sync($request->categories);
        }

        return response()->json([
            'status'  => true,
            'message' => 'Item batch stock created successfully.',
            'batch'   => new BatchResource($batch->load('item', 'variants.attributeValues.attribute', 'unit')),
        ], 201);
    }

    public function show($id)
    {
        $batch = ItemBatch::with(['item', 'variants.attributeValues.attribute', 'unit'])->find($id);

        if (!$batch) {
            return response()->json(['status' => false, 'message' => 'Batch not found.'], 404);
        }

        return response()->json([
            'status' => true,
            'batch'  => new BatchResource($batch),
        ], 200);
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'batch_number'        => 'nullable|string|max:255',
            'purchase_price'      => 'required|numeric|min:0',
            'quantity'            => 'required|numeric|min:0',
            'product_type'        => 'nullable|string|max:50',

            'variant_ids'         => 'nullable|array',
            'variant_ids.*'       => 'integer|exists:item_variants,id',

            'quantity_count'      => 'nullable|numeric|min:0',
            'purchase_date'       => 'nullable|date',
            'date_of_manufacture' => 'nullable|date',
            'date_of_expiry'      => 'nullable|date',
            'brand_name'          => 'nullable|string|max:255',
            'brand_id'            => 'nullable|integer',
            'replacement'         => 'nullable|numeric|min:0',

            'cost_price'          => 'nullable|numeric|min:0',
            'regular_price'       => 'nullable|numeric|min:0',
            'sale_price'          => 'nullable|numeric|min:0',
            'unit_of_measure'     => 'nullable|string|max:50',
            'unit_id'             => 'nullable|exists:measuring_units,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
        }

        $batch = ItemBatch::find($id);
        if (!$batch) {
            return response()->json(['status' => false, 'message' => 'Batch not found.'], 404);
        }

        $batch->update([
            'batch_number'       => $request->batch_number ?? $batch->batch_number,
            'purchase_price'     => $request->purchase_price,
            'quantity'           => $request->quantity,
            'quantity_count'     => $request->quantity_count,
            'purchase_date'      => $request->purchase_date,
            'date_of_manufacture' => $request->date_of_manufacture,
            'date_of_expiry'     => $request->date_of_expiry,
            'brand_name'         => $request->brand_name,
            'brand_id'           => $request->brand_id,
            'replacement'        => $request->replacement,
            'cost_price'         => $request->cost_price,
            'regular_price'      => $request->regular_price,
            'sale_price'         => $request->sale_price,
            'product_type'       => $request->product_type,
            'unit_of_measure'    => $request->unit_of_measure,
            'unit_id'            => $request->unit_id,
        ]);

        if ($request->filled('variant_ids')) {
            $batch->variants()->sync($request->variant_ids);
        }

        if ($request->filled('categories')) {
            $batch->categories()->sync($request->categories);
        }

        return response()->json([
            'status'  => true,
            'message' => 'Item batch stock updated successfully.',
            'batch'   => new BatchResource($batch->load('item', 'variants.attributeValues.attribute', 'unit')),
        ], 200);
    }

    public function destroy($id)
    {
        $batch = ItemBatch::find($id);
        if (!$batch) {
            return response()->json(['status' => false, 'message' => 'Batch not found.'], 404);
        }

        $batch->variants()->detach();
        $batch->categories()->detach();
        $batch->delete();

        return response()->json([
            'status'  => true,
            'message' => 'Item batch stock deleted successfully.',
        ], 200);
    }
}
