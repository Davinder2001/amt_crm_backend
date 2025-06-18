<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ItemResource;
use App\Models\{Item, Package};
use App\Services\{ItemService, SelectedCompanyService, VendorService};
use App\Helpers\ImageHelper;
use Illuminate\Http\{Request, JsonResponse};
use Illuminate\Support\Facades\{DB, Validator};

class ItemsController extends Controller
{
    public function index(): JsonResponse
    {
        $items = Item::with(['variants.attributeValues.attribute', 'taxes', 'categories', 'batches'])->get();
        return response()->json(ItemResource::collection($items));
    }

    public function store(Request $request): JsonResponse
    {
        $selectedCompany = SelectedCompanyService::getSelectedCompanyOrFail();
        $companyId = $selectedCompany->company_id;

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'measurement' => 'nullable|string',
            'quantity_count' => 'required|integer',
            'cost_price' => 'required|numeric|min:0',
            'vendor_name' => 'nullable|string|max:255',
            'vendor_id' => 'nullable|integer',
            'brand_id' => 'nullable|integer',
            'brand_name' => 'nullable|string|max:255',
            'tax_id' => 'nullable|integer|exists:taxes,id',
            'replacement' => 'nullable|string|max:255',
            'purchase_date' => 'nullable|date',
            'date_of_manufacture' => 'nullable|date',
            'date_of_expiry' => 'nullable|date',
            'product_type' => 'required|in:simple_product,variable_product',
            'sale_type' => 'nullable|string|max:255',
            'unit_of_measure' => 'required|in:pieces,unit',
            'regular_price' => 'required_if:product_type,simple_product|nullable|numeric|min:0',
            'sale_price' => 'nullable|numeric|min:0',
            'units_in_peace' => 'nullable|string|max:255',
            'price_per_unit' => 'nullable|string|max:255',
            'variants' => 'required_if:product_type,variable_product|array',
            'variants.*.variant_regular_price' => 'required_with:variants|numeric|min:0',
            'variants.*.variant_sale_price' => 'nullable|numeric|min:0',
            'variants.*.variant_stock' => 'required_with:variants|integer|min:0',
            'variants.*.variant_units_in_peace' => 'nullable|string|max:255',
            'variants.*.variant_price_per_unit' => 'nullable|string|max:255',
            'variants.*.attributes' => 'required_with:variants|array',
            'variants.*.attributes.*.attribute_id' => 'required|exists:attributes,id',
            'variants.*.attributes.*.attribute_value_id' => 'required|exists:attribute_values,id',
            'featured_image' => 'nullable|image|mimes:jpg,jpeg,png|max:5120',
            'images' => 'nullable|array',
            'images.*' => 'image|mimes:jpg,jpeg,png|max:5120',
            'categories' => 'nullable|array',
            'categories.*' => 'integer|exists:categories,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation errors.', 'errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        $data['company_id'] = $companyId;

        $package = Package::find($selectedCompany->company->package_id);
        if (!$package) {
            return response()->json(['success' => false, 'message' => 'No package found for the selected company.'], 404);
        }

        $itemQuery = Item::where('company_id', $companyId);
        $now = now();

        if ($package->package_type === 'monthly') {
            $itemQuery->whereYear('created_at', $now->year)->whereMonth('created_at', $now->month);
        } else {
            $itemQuery->whereYear('created_at', $now->year);
        }

        if ($itemQuery->count() >= ($package->items_number ?? 0)) {
            return response()->json(['success' => false, 'message' => 'Item limit reached for your package.'], 403);
        }

        try {
            DB::beginTransaction();

            $data['item_code'] = ItemService::generateNextItemCode($companyId);

            if (!empty($data['vendor_name'])) {
                VendorService::createIfNotExists($data['vendor_name'], $companyId);
            }

            $data['images'] = ImageHelper::processImages($request->file('images') ?? []);
            $data['featured_image'] = $request->hasFile('featured_image') ? ImageHelper::saveImage($request->file('featured_image'), 'featured_') : null;

            $item = Item::create(array_merge(
                $data,
                [
                    'invoice_id' => $data['invoice_no'] ?? null,
                ]
            ));

            ItemService::createItemVariants($item, $data['variants'] ?? [], $data['images']);
            ItemService::assignCategories($item, $data['categories'] ?? null, $companyId);
            ItemService::assignTax($item, $data['tax_id'] ?? null);
            ItemService::createBatch($item, $data);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Item and stock batch added successfully.',
                'item' => new ItemResource($item->load('variants.attributeValues', 'categories')),
            ], 201);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Something went wrong while creating the item.', 'error' => $e->getMessage()], 500);
        }
    }

    public function show($id): JsonResponse
    {
        $item = Item::with(['variants.attributeValues.attribute', 'taxes', 'categories'])->find($id);
        return $item ? response()->json(new ItemResource($item)) : response()->json(['message' => 'Item not found.'], 404);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'measurement' => 'sometimes|nullable|string',
            'quantity_count' => 'sometimes|nullable|integer',
            'cost_price' => 'sometimes|nullable|numeric|min:0',
            'vendor_name' => 'sometimes|nullable|string|max:255',
            'vendor_id' => 'sometimes|nullable|integer',
            'brand_id' => 'sometimes|nullable|integer',
            'brand_name' => 'sometimes|nullable|string|max:255',
            'tax_id' => 'sometimes|nullable|integer|exists:taxes,id',
            'replacement' => 'sometimes|nullable|string|max:255',
            'purchase_date' => 'sometimes|nullable|date',
            'date_of_manufacture' => 'sometimes|nullable|date',
            'date_of_expiry' => 'sometimes|nullable|date',
            'product_type' => 'sometimes|in:simple_product,variable_product',
            'sale_type' => 'sometimes|nullable|string|max:255',
            'unit_of_measure' => 'sometimes|in:pieces,unit',
            'regular_price' => 'sometimes|nullable|numeric|min:0',
            'sale_price' => 'sometimes|nullable|numeric|min:0',
            'units_in_peace' => 'sometimes|nullable|string|max:255',
            'price_per_unit' => 'sometimes|nullable|string|max:255',
            'variants' => 'sometimes|nullable|array',
            'variants.*.variant_regular_price' => 'required_with:variants|numeric|min:0',
            'variants.*.variant_sale_price' => 'sometimes|nullable|numeric|min:0',
            'variants.*.variant_stock' => 'sometimes|nullable|integer|min:0',
            'variants.*.variant_units_in_peace' => 'sometimes|nullable|string|max:255',
            'variants.*.variant_price_per_unit' => 'sometimes|nullable|string|max:255',
            'variants.*.attributes' => 'required_with:variants|array',
            'variants.*.attributes.*.attribute_id' => 'required|exists:attributes,id',
            'variants.*.attributes.*.attribute_value_id' => 'required|exists:attribute_values,id',
            'featured_image' => 'sometimes|nullable|image|mimes:jpg,jpeg,png|max:5120',
            'images' => 'sometimes|array',
            'images.*' => 'image|mimes:jpg,jpeg,png|max:5120',
            'removed_images' => 'sometimes|array',
            'removed_images.*' => 'string',
            'categories' => 'sometimes|nullable|array',
            'categories.*' => 'integer|exists:categories,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation errors.', 'errors' => $validator->errors()], 422);
        }

        $selectedCompany = SelectedCompanyService::getSelectedCompanyOrFail();
        $companyId = $selectedCompany->company->id;
        $item = Item::with('variants', 'categories', 'batches')->find($id);

        if (!$item) return response()->json(['success' => false, 'message' => 'Item not found.'], 404);
        if (!$selectedCompany->super_admin && $item->company_id !== $companyId) return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);

        $data = array_merge($validator->validated(), ['company_id' => $companyId]);

        try {
            DB::beginTransaction();

            $data['images'] = ImageHelper::updateImages(
                $request->file('images') ?? [],
                is_array($item->images) ? $item->images : (json_decode($item->images, true) ?? []),
                $data['removed_images'] ?? []
            );

            $data['featured_image'] = $request->hasFile('featured_image')
                ? ImageHelper::saveImage($request->file('featured_image'), 'featured_')
                : $item->featured_image;

            $data['item_code'] = $item->item_code ?: ItemService::generateNextItemCode($companyId);
            $item->update($data);

            // $item->variants()->delete();
            ItemService::createItemVariants($item, $data['variants'] ?? [], $data['images']);
            $item->categories()->sync([]);
            ItemService::assignCategories($item, $data['categories'] ?? null, $companyId);
            ItemService::assignTax($item, $data['tax_id'] ?? null);

            if ($item->batches()->exists()) {
                $batch = $item->batches()->first();
                $batch->update([
                    'purchase_price' => $data['cost_price'] ?? $batch->purchase_price,
                    'quantity' => $data['quantity_count'] ?? $batch->quantity,
                    'unit_of_measure' => $data['unit_of_measure'] ?? $batch->unit_of_measure,
                    'units_in_peace' => $data['units_in_peace'] ?? $batch->units_in_peace,
                    'price_per_unit' => $data['price_per_unit'] ?? $batch->price_per_unit,
                    'date_of_manufacture' => $data['date_of_manufacture'] ?? $batch->date_of_manufacture,
                    'date_of_expiry' => $data['date_of_expiry'] ?? $batch->date_of_expiry,
                ]);
            }

            DB::commit();

            return response()->json(['success' => true, 'message' => 'Item updated successfully.', 'item' => new ItemResource($item->load('variants.attributeValues', 'categories'))]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Something went wrong while updating the item.', 'error' => $e->getMessage()], 500);
        }
    }

    public function destroy($id): JsonResponse
    {
        $item = Item::find($id);
        if (!$item) return response()->json(['message' => 'Item not found.'], 404);
        $item->delete();
        return response()->json(['message' => 'Item deleted successfully.'], 201);
    }
}
