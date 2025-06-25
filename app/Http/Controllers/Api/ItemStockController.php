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
    /**
     * Display a listing of the item batches.
     *
     */
    public function index()
    {
        $batches = ItemBatch::with(['item', 'variants.attributeValues.attribute', 'unit', 'vendor'])->latest()->get();

        return response()->json([
            'status'  => true,
            'batches' => BatchResource::collection($batches),
        ]);
    }

    /**
     * Store a newly created item batch in storage.
     *
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'item_id'           => 'required|exists:store_items,id',
            'quantity_count'    => 'required|numeric|min:0',
            'product_type'      => 'required|string|max:50',
            'unit_of_measure'   => 'nullable|string|max:50',
            'invoice_number'    => 'nullable|string|max:500',
            'vendor_id'         => 'nullable|exists:store_vendors,id',
            'tax_type'          => 'required|in:include,exclude',

            'variants'                                   => 'nullable|array',
            'variants.*.variant_regular_price'           => 'nullable|numeric',
            'variants.*.variant_sale_price'              => 'nullable|numeric',
            'variants.*.variant_units_in_peace'          => 'nullable|numeric',
            'variants.*.variant_price_per_unit'          => 'nullable|numeric',
            'variants.*.variant_stock'                   => 'nullable|numeric',
            'variants.*.images'                          => 'nullable|array',
            'variants.*.attributes'                      => 'nullable|array',
            'variants.*.attributes.*.attribute_id'       => 'required|integer',
            'variants.*.attributes.*.attribute_value_id' => 'required|integer',

            'purchase_date'       => 'nullable|date',
            'date_of_manufacture' => 'nullable|date',
            'date_of_expiry'      => 'nullable|date',
            'replacement'         => 'nullable|string|max:255',
            'cost_price'          => 'nullable|numeric|min:0',
            'regular_price'       => 'nullable|numeric',
            'sale_price'          => 'nullable|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
        }

        $companyId = SelectedCompanyService::getSelectedCompanyOrFail()->company->id;

        $batch = ItemBatch::create([
            'company_id'          => $companyId,
            'item_id'             => $request->item_id,
            'invoice_number'      => $request->invoice_number,
            'quantity'            => $request->quantity_count,
            'purchase_date'       => $request->purchase_date,
            'date_of_manufacture' => $request->date_of_manufacture,
            'date_of_expiry'      => $request->date_of_expiry,
            'replacement'         => $request->replacement,
            'cost_price'          => $request->cost_price,
            'product_type'        => $request->product_type,
            'vendor_id'           => $request->vendor_id,
            'unit_of_measure'     => $request->unit_of_measure,
            'regular_price'       => $request->regular_price,
            'sale_price'          => $request->sale_price,
            'tax_type'            => $request->tax_type,
        ]);

        foreach ($request->variants ?? [] as $variantData) {
            $variant = new ItemVariant([
                'item_id'                 => $request->item_id,
                'variant_regular_price'   => $variantData['variant_regular_price'],
                'variant_sale_price'      => $variantData['variant_sale_price'],
                'variant_units_in_peace'  => $variantData['variant_units_in_peace'] ?? null,
                'variant_price_per_unit'  => $variantData['variant_price_per_unit'] ?? null, 
                'quntity'                 => $variantData['variant_stock'],
                'stock'                   => $variantData['variant_stock'],
                'images'                  => $variantData['images'] ?? [],
            ]);
            $variant->batch_id = $batch->id;
            $variant->save();

            $attributeSync = [];
            foreach ($variantData['attributes'] ?? [] as $attr) {
                $attributeSync[$attr['attribute_value_id']] = ['attribute_id' => $attr['attribute_id']];
            }
            $variant->attributeValues()->sync($attributeSync);
        }

        return response()->json([
            'status'  => true,
            'message' => 'Item batch created successfully.',
            'batch'   => new BatchResource($batch->load('item', 'variants.attributeValues.attribute', 'unit')),
        ], 201);
    }

    /**
     * Display the specified item batch.
     *
    */
    public function show($id)
    {
        $batch = ItemBatch::with(['item', 'variants.attributeValues.attribute', 'unit', 'vendor'])->find($id);

        if (!$batch) {
            return response()->json([
                'status' => false, 
                'message' => 'Batch not found.'
            ], 404);
        }

        return response()->json([
            'status' => true, 
            'batch' => new BatchResource($batch)
        ], 201);
    }

    /**
     * Update the specified item batch in storage.
     *
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'item_id'                                   => 'sometimes|exists:store_items,id',
            'quantity_count'                            => 'sometimes|numeric|min:0',
            'product_type'                              => 'sometimes|string|max:50',
            'unit_of_measure'                           => 'sometimes|string|max:50',
            'invoice_number'                            => 'sometimes|string|max:50',
            'vendor_id'                                 => 'sometimes|exists:store_vendors,id',
            'tax_type'                                  => 'sometimes|in:include,exclude',

            'variants'                                  => 'sometimes|array',
            'variants.*.id'                             => 'sometimes|exists:item_variants,id',
            'variants.*.variant_regular_price'          => 'sometimes|numeric',
            'variants.*.variant_sale_price'             => 'sometimes|numeric',
            'variants.*.variant_units_in_peace'         => 'sometimes|numeric',
            'variants.*.variant_price_per_unit'         => 'sometimes|numeric',
            'variants.*.variant_stock'                  => 'sometimes|numeric',
            'variants.*.images'                         => 'sometimes|array',
            'variants.*.attributes'                     => 'sometimes|array',
            'variants.*.attributes.*.attribute_id'      => 'required_with:variants.*.attributes|integer',
            'variants.*.attributes.*.attribute_value_id'=> 'required_with:variants.*.attributes|integer',

            'purchase_date'                             => 'sometimes|date',
            'date_of_manufacture'                       => 'sometimes|date',
            'date_of_expiry'                            => 'sometimes|date',
            'replacement'                               => 'sometimes|string|max:255',
            'cost_price'                                => 'sometimes|numeric|min:0',
            'regular_price'                             => 'sometimes|numeric',
            'sale_price'                                => 'sometimes|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false, 
                'errors' => $validator->errors()
            ], 422);
        }

        $batch = ItemBatch::findOrFail($id);

        $fillableFields = [
            'item_id'             => 'item_id',
            'quantity_count'      => 'quantity',
            'vendor_id'           => 'vendor_id',
            'invoice_number'      => 'invoice_number',
            'purchase_date'       => 'purchase_date',
            'date_of_manufacture' => 'date_of_manufacture',
            'date_of_expiry'      => 'date_of_expiry',
            'replacement'         => 'replacement',
            'cost_price'          => 'cost_price',
            'product_type'        => 'product_type',
            'unit_of_measure'     => 'unit_of_measure',
            'regular_price'       => 'regular_price',
            'sale_price'          => 'sale_price',
            'tax_type'            => 'tax_type',
        ];

        $updateData = [];
        foreach ($fillableFields as $requestKey => $dbColumn) {
            if ($request->has($requestKey)) {
                $updateData[$dbColumn] = $request->$requestKey;
            }
        }

        $batch->update($updateData);

        if ($request->has('variants')) {
            ItemVariant::where('batch_id', $batch->id)->delete();

            foreach ($request->variants as $variantData) {
                $variant = new ItemVariant();
                $variant->item_id = $request->item_id ?? $batch->item_id;
                $variant->batch_id = $batch->id;

                $variantFields = [
                    'variant_regular_price',
                    'variant_sale_price',
                    'variant_units_in_peace',
                    'variant_price_per_unit',
                    'variant_stock',
                    'images'
                ];

                foreach ($variantFields as $field) {
                    if (isset($variantData[$field])) {
                        if ($field === 'variant_stock') {
                            $variant->stock = $variantData[$field];
                        } else {
                            $variant->{$field} = $variantData[$field];
                        }
                    }
                }

                $variant->save();

                if (isset($variantData['attributes'])) {
                    $attributeSync = [];
                    foreach ($variantData['attributes'] as $attr) {
                        $attributeSync[$attr['attribute_value_id']] = ['attribute_id' => $attr['attribute_id']];
                    }
                    $variant->attributeValues()->sync($attributeSync);
                }
            }
        }

        return response()->json([
            'status'  => true,
            'message' => 'Item batch updated successfully.',
            'batch'   => new BatchResource($batch->load('item', 'variants.attributeValues.attribute', 'unit')),
        ], 200);
    }

    /**
     * Remove the specified item batch from storage.
     *
     */
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
