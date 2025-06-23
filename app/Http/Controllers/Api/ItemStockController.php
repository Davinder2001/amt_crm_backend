<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ItemBatch;
use App\Models\ItemVariant;
use Illuminate\Support\Facades\Validator;
use App\Services\SelectedCompanyService;
use App\Http\Resources\BatchResource;

class ItemStockController extends Controller
{
    public function index()
    {
        $batches = ItemBatch::with(['item', 'variants', 'unit'])->latest()->get();

        return response()->json([
            'status'  => true,
            'batches' => BatchResource::collection($batches),
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'item_id'               => 'required|exists:store_items,id',
            'quantity_count'        => 'required|numeric|min:0',
            'product_type'          => 'required|string|max:50',
            'unit_of_measure'       => 'nullable|string|max:50',
            'unit_id'               => 'nullable|exists:measuring_units,id',

            'variants'              => 'nullable|array',
            'variants.*.variant_regular_price'   => 'nullable|numeric',
            'variants.*.variant_sale_price'      => 'nullable|numeric',
            'variants.*.variant_units_in_peace'  => 'nullable|numeric',
            'variants.*.variant_price_per_unit'  => 'nullable|numeric',
            'variants.*.stock'                   => 'nullable|numeric',
            'variants.*.images'                  => 'nullable|array',

            'purchase_date'         => 'nullable|date',
            'date_of_manufacture'   => 'nullable|date',
            'date_of_expiry'        => 'nullable|date',
            'replacement'           => 'nullable|string|max:255',
            'cost_price'            => 'nullable|numeric|min:0',
            'regular_price'         => 'nullable|numeric|min:0',
            'sale_price'            => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
        }

        $companyId = SelectedCompanyService::getSelectedCompanyOrFail()->company->id;

        $batch = ItemBatch::create([
            'company_id'          => $companyId,
            'item_id'             => $request->item_id,
            'quantity'            => $request->quantity_count,
            'purchase_date'       => $request->purchase_date,
            'date_of_manufacture' => $request->date_of_manufacture,
            'date_of_expiry'      => $request->date_of_expiry,
            'replacement'         => $request->replacement,
            'cost_price'          => $request->cost_price,
            'regular_price'       => $request->regular_price,
            'sale_price'          => $request->sale_price,
            'product_type'        => $request->product_type,
            'unit_of_measure'     => $request->unit_of_measure,
            'unit_id'             => $request->unit_id,
        ]);

        if ($request->filled('variants')) {
            foreach ($request->variants as $variantData) {
                $batch->variants()->create([
                    'item_id'                 => $request->item_id,
                    'batch_id'                => $batch->id,
                    'variant_regular_price'   => $variantData['variant_regular_price'] ?? null,
                    'variant_sale_price'      => $variantData['variant_sale_price'] ?? null,
                    'variant_units_in_peace'  => $variantData['variant_units_in_peace'] ?? null,
                    'variant_price_per_unit'  => $variantData['variant_price_per_unit'] ?? null,
                    'stock'                   => $variantData['stock'] ?? null,
                    'images'                  => $variantData['images'] ?? null,
                ]);
            }
        }

        return response()->json([
            'status'  => true,
            'message' => 'Item batch stock created successfully.',
            'batch'   => new BatchResource($batch->load('item', 'variants', 'unit')),
        ], 201);
    }

    public function show($id)
    {
        $batch = ItemBatch::with(['item', 'variants', 'unit'])->find($id);

        if (!$batch) {
            return response()->json(['status' => false, 'message' => 'Batch not found.'], 404);
        }

        return response()->json([
            'status' => true,
            'batch'  => new BatchResource($batch),
        ]);
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'item_id'               => 'required|exists:store_items,id',
            'quantity_count'        => 'required|numeric|min:0',
            'product_type'          => 'nullable|string|max:50',
            'unit_of_measure'       => 'nullable|string|max:50',
            'unit_id'               => 'nullable|exists:measuring_units,id',

            'variants'              => 'nullable|array',
            'variants.*.id'                     => 'nullable|exists:item_variants,id',
            'variants.*.variant_regular_price' => 'nullable|numeric',
            'variants.*.variant_sale_price'    => 'nullable|numeric',
            'variants.*.variant_units_in_peace'=> 'nullable|numeric',
            'variants.*.variant_price_per_unit'=> 'nullable|numeric',
            'variants.*.stock'                 => 'nullable|numeric',
            'variants.*.images'                => 'nullable|array',

            'purchase_date'         => 'nullable|date',
            'date_of_manufacture'   => 'nullable|date',
            'date_of_expiry'        => 'nullable|date',
            'replacement'           => 'nullable|string|max:255',
            'cost_price'            => 'nullable|numeric|min:0',
            'regular_price'         => 'nullable|numeric|min:0',
            'sale_price'            => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
        }

        $batch = ItemBatch::findOrFail($id);

        $batch->update([
            'quantity'            => $request->quantity_count,
            'purchase_date'       => $request->purchase_date,
            'date_of_manufacture' => $request->date_of_manufacture,
            'date_of_expiry'      => $request->date_of_expiry,
            'replacement'         => $request->replacement,
            'cost_price'          => $request->cost_price,
            'regular_price'       => $request->regular_price,
            'sale_price'          => $request->sale_price,
            'product_type'        => $request->product_type,
            'unit_of_measure'     => $request->unit_of_measure,
            'unit_id'             => $request->unit_id,
        ]);

        if ($request->filled('variants')) {
            foreach ($request->variants as $variantData) {
                if (!empty($variantData['id'])) {
                    $variant = ItemVariant::find($variantData['id']);
                    if ($variant) {
                        $variant->update($variantData);
                    }
                } else {
                    $batch->variants()->create(array_merge($variantData, [
                        'item_id'  => $request->item_id,
                        'batch_id' => $batch->id,
                    ]));
                }
            }
        }

        return response()->json([
            'status'  => true,
            'message' => 'Item batch stock updated successfully.',
            'batch'   => new BatchResource($batch->load('item', 'variants', 'unit')),
        ]);
    }

    public function destroy($id)
    {
        $batch = ItemBatch::find($id);

        if (!$batch) {
            return response()->json(['status' => false, 'message' => 'Batch not found.'], 404);
        }

        $batch->variants()->delete();
        $batch->delete();

        return response()->json([
            'status'  => true,
            'message' => 'Item batch stock deleted successfully.',
        ]);
    }
}
