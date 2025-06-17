<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ItemResource;
use App\Models\{Item, Package};
use App\Services\{ItemService, SelectedCompanyService, VendorService};
use App\Helpers\ImageHelper;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ItemsController extends Controller
{
    /**
     * Display a listing of the items.
     *
     */
    public function index(): JsonResponse
    {
        $items = Item::with(['variants.attributeValues.attribute', 'taxes', 'categories', 'batches'])->get();
        return response()->json(ItemResource::collection($items));
    }

    /**
     * Store a newly created item in storage.
     *
     */
    public function store(Request $request): JsonResponse
    {
        $selectedCompany = SelectedCompanyService::getSelectedCompanyOrFail();
        $companyId       = $selectedCompany->company_id;

        /* -----------------------------------------------------------------
     | 1. VALIDATION
     * -----------------------------------------------------------------*/
        $validator = Validator::make($request->all(), [

            /* core item data */
            'name'            => 'required|string|max:255',
            'measurement'     => 'nullable|string',
            'quantity_count'  => 'required|integer',
            'cost_price'      => 'required|numeric|min:0',

            /* vendor / brand */
            'vendor_name'     => 'nullable|string|max:255',
            'vendor_id'       => 'nullable|integer',
            'brand_id'        => 'nullable|integer',
            'brand_name'      => 'nullable|string|max:255',

            /* tax */
            'tax_id'          => 'nullable|integer|exists:taxes,id',

            /* dates & misc. */
            'replacement'         => 'nullable|string|max:255',
            'purchase_date'       => 'nullable|date',
            'date_of_manufacture' => 'nullable|date',
            'date_of_expiry'      => 'nullable|date',

            /* product meta */
            'product_type'    => 'required|in:simple_product,variable_product',
            'sale_type'       => 'nullable|string|max:255',
            'unit_of_measure' => 'required|in:pieces,unit',

            /* pricing */
            'regular_price'   => 'required_if:product_type,simple_product|nullable|numeric|min:0',
            'sale_price'      => 'nullable|numeric|min:0',
            'units_in_peace'  => 'nullable|string|max:255',
            'price_per_unit'  => 'nullable|string|max:255',

            /* variants (only for variable products) */
            'variants'                                         => 'required_if:product_type,variable_product|array',
            'variants.*.variant_regular_price'                 => 'required_with:variants|numeric|min:0',
            'variants.*.variant_sale_price'                    => 'nullable|numeric|min:0',
            'variants.*.variant_stock'                         => 'required_with:variants|integer|min:0',
            'variants.*.variant_units_in_peace'                => 'nullable|string|max:255',
            'variants.*.variant_price_per_unit'                => 'nullable|string|max:255',
            'variants.*.attributes'                            => 'required_with:variants|array',
            'variants.*.attributes.*.attribute_id'             => 'required|exists:attributes,id',
            'variants.*.attributes.*.attribute_value_id'       => 'required|exists:attribute_values,id',

            /* images / files */
            'featured_image' => 'nullable|image|mimes:jpg,jpeg,png|max:5120',
            'images'         => 'nullable|array',
            'images.*'       => 'image|mimes:jpg,jpeg,png|max:5120',

            /* categories */
            'categories'     => 'nullable|array',
            'categories.*'   => 'integer|exists:categories,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        /* -----------------------------------------------------------------
     | 2. PACKAGE LIMIT CHECK
     * -----------------------------------------------------------------*/
        $data               = $validator->validated();
        $data['company_id'] = $companyId;

        $package = Package::find($selectedCompany->company->package_id);
        if (!$package) {
            return response()->json([
                'success' => false,
                'message' => 'No package found for the selected company.',
            ], 404);
        }

        $itemQuery = Item::where('company_id', $companyId);
        $now       = now();

        if ($package->package_type === 'monthly') {
            $itemQuery->whereYear('created_at', $now->year)
                ->whereMonth('created_at', $now->month);
        } else {
            $itemQuery->whereYear('created_at', $now->year);
        }

        if ($itemQuery->count() >= ($package->items_number ?? 0)) {
            return response()->json([
                'success' => false,
                'message' => 'Item limit reached for your package.',
            ], 403);
        }

        /* -----------------------------------------------------------------
     | 3. STORE ITEM
     * -----------------------------------------------------------------*/
        try {
            DB::beginTransaction();

            $data['item_code'] = ItemService::generateNextItemCode($companyId);

            // autocreate vendor if needed
            if (!empty($data['vendor_name'])) {
                VendorService::createIfNotExists($data['vendor_name'], $companyId);
            }

            // images handling
            $data['images']         = ImageHelper::processImages($request->file('images') ?? []);
            $data['featured_image'] = $request->hasFile('featured_image')
                ? ImageHelper::saveImage($request->file('featured_image'), 'featured_')
                : null;

            /** @var \App\Models\Item $item */
            $item = Item::create([
                'company_id'          => $data['company_id'],
                'item_code'           => $data['item_code'],
                'brand_id'            => $data['brand_id'] ?? null,
                'brand_name'          => $data['brand_name'] ?? null,

                'name'                => $data['name'],
                'measurement'         => $data['measurement'] ?? null,
                'quantity_count'      => $data['quantity_count'],
                'unit_of_measure'     => $data['unit_of_measure'],

                // pricing
                'cost_price'      => $data['cost_price'],
                'regular_price'   => $data['regular_price'] ?? null,
                'sale_price'      => $data['sale_price'] ?? null,
                'units_in_peace'  => $data['units_in_peace'] ?? null,
                'price_per_unit'  => $data['price_per_unit'] ?? null,

                // product meta
                'product_type'    => $data['product_type'],
                'sale_type'       => $data['sale_type'] ?? null,

                // vendor / dates / misc.
                'vendor_name'         => $data['vendor_name'] ?? null,
                'vendor_id'           => $data['vendor_id'] ?? null,
                'replacement'         => $data['replacement'] ?? null,
                'purchase_date'       => $data['purchase_date'] ?? null,
                'date_of_manufacture' => $data['date_of_manufacture'] ?? null,
                'date_of_expiry'      => $data['date_of_expiry'] ?? null,

                // media
                'featured_image' => $data['featured_image'],
                'images'         => $data['images'],

                // optional invoice number (came in as invoice_no)
                'invoice_id'     => $data['invoice_no'] ?? null,
            ]);

            /* ------------------------------------------
         | Relationships
         *------------------------------------------*/
            ItemService::createItemVariants($item, $data['variants'] ?? [], $data['images']);
            ItemService::assignCategories($item, $data['categories'] ?? null, $companyId);
            ItemService::assignTax($item, $data['tax_id'] ?? null);
            ItemService::createBatch($item, $data);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Item and stock batch added successfully.',
                'item'    => new ItemResource(
                    $item->load('variants.attributeValues', 'categories')
                ),
            ], 201);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong while creating the item.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }


    /**
     * Display the specified item.
     *
     */
    public function show($id): JsonResponse
    {
        $item = Item::with(['variants.attributeValues.attribute', 'taxes', 'categories'])->find($id);

        if (!$item) {
            return response()->json(['message' => 'Item not found.'], 404);
        }

        return response()->json(new ItemResource($item));
    }

    /**
     * Update the specified item in storage.
     *
     */
    public function update(Request $request, int $id): JsonResponse
    {
        /* ────────────────────── 1. Validation ────────────────────── */
        $validator = Validator::make($request->all(), [
            /* core item data */
            'name'            => 'sometimes|required|string|max:255',
            'measurement'     => 'sometimes|nullable|string',
            'quantity_count'  => 'sometimes|nullable|integer',
            'cost_price'      => 'sometimes|nullable|numeric|min:0',

            /* vendor / brand */
            'vendor_name'     => 'sometimes|nullable|string|max:255',
            'vendor_id'       => 'sometimes|nullable|integer',
            'brand_id'        => 'sometimes|nullable|integer',
            'brand_name'      => 'sometimes|nullable|string|max:255',

            /* tax */
            'tax_id'          => 'sometimes|nullable|integer|exists:taxes,id',

            /* dates & misc. */
            'replacement'         => 'sometimes|nullable|string|max:255',
            'purchase_date'       => 'sometimes|nullable|date',
            'date_of_manufacture' => 'sometimes|nullable|date',
            'date_of_expiry'      => 'sometimes|nullable|date',

            /* product meta */
            'product_type'    => 'sometimes|in:simple_product,variable_product',
            'sale_type'       => 'sometimes|nullable|string|max:255',
            'unit_of_measure' => 'sometimes|in:pieces,unit',

            /* pricing */
            'regular_price'   => 'sometimes|nullable|numeric|min:0',
            'sale_price'      => 'sometimes|nullable|numeric|min:0',
            'units_in_peace'  => 'sometimes|nullable|string|max:255',
            'price_per_unit'  => 'sometimes|nullable|string|max:255',

            /* variants (only for variable products) */
            'variants'                                         => 'sometimes|nullable|array',
            'variants.*.variant_regular_price'                 => 'required_with:variants|numeric|min:0',
            'variants.*.variant_sale_price'                    => 'sometimes|nullable|numeric|min:0',
            'variants.*.variant_stock'                         => 'sometimes|nullable|integer|min:0',
            'variants.*.variant_units_in_peace'                => 'sometimes|nullable|string|max:255',
            'variants.*.variant_price_per_unit'                => 'sometimes|nullable|string|max:255',
            'variants.*.attributes'                            => 'required_with:variants|array',
            'variants.*.attributes.*.attribute_id'             => 'required|exists:attributes,id',
            'variants.*.attributes.*.attribute_value_id'       => 'required|exists:attribute_values,id',

            /* images */
            'featured_image'   => 'sometimes|nullable|image|mimes:jpg,jpeg,png|max:5120',
            'images'           => 'sometimes|array',
            'images.*'         => 'image|mimes:jpg,jpeg,png|max:5120',
            'removed_images'   => 'sometimes|array',
            'removed_images.*' => 'string',

            /* categories */
            'categories'     => 'sometimes|nullable|array',
            'categories.*'   => 'integer|exists:categories,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        /* ────────────────────── 2. Authorisation + package ────────────────────── */
        $selectedCompany = SelectedCompanyService::getSelectedCompanyOrFail();
        $companyId       = $selectedCompany->company->id;

        /** @var \App\Models\Item|null $item */
        $item = Item::with('variants', 'categories')->find($id);
        if (!$item) {
            return response()->json(['success' => false, 'message' => 'Item not found.'], 404);
        }
        if (!$selectedCompany->super_admin && $item->company_id !== $companyId) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        /* ────────────────────── 3. Merge validated data ────────────────────── */
        $data = array_merge($validator->validated(), ['company_id' => $companyId]);

        DB::beginTransaction();
        try {
            /* ---------- Images ---------- */
            $data['images'] = ImageHelper::updateImages(
                $request->file('images') ?? [],
                is_array($item->images) ? $item->images : (json_decode($item->images, true) ?? []),
                $data['removed_images'] ?? []
            );

            $data['featured_image'] = $request->hasFile('featured_image')
                ? ImageHelper::saveImage($request->file('featured_image'), 'featured_')
                : $item->featured_image;

            /* ---------- Item code (if missing) ---------- */
            if (empty($item->item_code)) {
                $data['item_code'] = ItemService::generateNextItemCode($companyId);
            }

            /* ---------- Update item ---------- */
            $item->update($data);

            /* ---------- Variants ---------- */
            $item->variants()->delete(); // simplest: re-insert
            ItemService::createItemVariants($item, $data['variants'] ?? [], $data['images']);

            /* ---------- Categories / Tax ---------- */
            $item->categories()->sync([]);
            ItemService::assignCategories($item, $data['categories'] ?? null, $companyId);
            ItemService::assignTax($item, $data['tax_id'] ?? null);

            /* ---------- Batch (update or create) ---------- */
            ItemService::createBatch($item, $data);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Item updated successfully.',
                'item'    => new ItemResource(
                    $item->load('variants.attributeValues', 'categories')
                ),
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong while updating the item.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }


    /**
     * Remove the specified item from storage.
     *
     */
    public function destroy($id): JsonResponse
    {
        $item = Item::find($id);

        if (!$item) {
            return response()->json([
                'message' => 'Item not found.'
            ], 404);
        }

        $item->delete();
        return response()->json([
            'message' => 'Item deleted successfully.'
        ], 201);
    }
}
