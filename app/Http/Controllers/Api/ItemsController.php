<?php

namespace App\Http\Controllers\Api;

use App\Models\{Item, StoreVendor, ItemVariant, AttributeValue};
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Services\SelectedCompanyService;
use App\Http\Resources\ItemResource;

class ItemsController extends Controller
{
    /**
     * Display a listing of the items.
     */
    public function index(): JsonResponse
    {
        $items = Item::with('variants.attributeValues')->get();
        return response()->json(ItemResource::collection($items));
    }

    /**
     * Store a newly created item in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $selectedCompany = SelectedCompanyService::getSelectedCompanyOrFail();

        $validator = Validator::make($request->all(), [
            'name'                  => 'required|string|max:255',
            'quantity_count'        => 'required|integer',
            'measurement'           => 'nullable|string',
            'purchase_date'         => 'nullable|date',
            'date_of_manufacture'   => 'required|date',
            'date_of_expiry'        => 'nullable|date',
            'brand_name'            => 'required|string|max:255',
            'replacement'           => 'nullable|string|max:255',
            'category'              => 'nullable|string|max:255',
            'vendor_name'           => 'nullable|string|max:255',
            'cost_price'            => 'required|numeric|min:0',
            'selling_price'         => 'required|numeric|min:0',
            'availability_stock'    => 'required|integer',
            'images.*'              => 'nullable|image|mimes:jpg,jpeg,png|max:5120',
            'variants'              => 'nullable|array',
            'variants.*.price'      => 'required_with:variants|numeric|min:0',
            'variants.*.stock'      => 'required_with:variants|integer|min:0',
            'variants.*.attributes' => 'required_with:variants|array'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation errors.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();
        $data['company_id'] = $selectedCompany->company_id;

        $lastItemCode = Item::where('company_id', $data['company_id'])->max('item_code');
        $data['item_code'] = $lastItemCode ? $lastItemCode + 1 : 1;

        if (!empty($data['vendor_name'])) {
            StoreVendor::firstOrCreate([
                'vendor_name' => $data['vendor_name'],
                'company_id'  => $data['company_id'],
            ]);
        }

        $imageLinks = [];
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $filename = uniqid('item_') . '.' . $image->getClientOriginalExtension();
                $image->move(public_path('uploads/items'), $filename);
                $imageLinks[] = asset('uploads/items/' . $filename);
            }
        }

        $data['images'] = $imageLinks;

        $item = Item::create($data);

        if (isset($data['variants'])) {
            foreach ($data['variants'] as $variantData) {
                $variant = $item->variants()->create([
                    'price' => $variantData['price'],
                    'stock' => $variantData['stock'],
                    'images' => $imageLinks,
                ]);

                foreach ($variantData['attributes'] as $attributeId => $valueId) {
                    $variant->attributeValues()->attach($valueId, ['attribute_id' => $attributeId]);
                }
            }
        }

        return response()->json([
            'message' => 'Item added successfully.',
            'item'    => $item->load('variants.attributeValues'),
        ], 201);
    }

    /**
     * Display the specified item.
     */
    public function show($id): JsonResponse
    {
        $selectedCompany = SelectedCompanyService::getSelectedCompanyOrFail();
        $item = Item::with('variants.attributeValues')->find($id);

        if (!$item) {
            return response()->json(['message' => 'Item not found.'], 404);
        }

        if (!$selectedCompany->super_admin && $item->company_id !== $selectedCompany->company_id) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        return response()->json($item);
    }

    /**
     * Update the specified item in storage.
     */
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
            'cost_price'          => 'sometimes|required|numeric|min:0',
            'selling_price'       => 'sometimes|required|numeric|min:0',
            'images.*'            => 'nullable|image|mimes:jpg,jpeg,png|max:5120',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation errors.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();

        if ($request->hasFile('images')) {
            $newImages = [];
            foreach ($request->file('images') as $image) {
                $filename = uniqid('item_') . '.' . $image->getClientOriginalExtension();
                $image->move(public_path('uploads/items'), $filename);
                $newImages[] = asset('uploads/items/' . $filename);
            }

            $existingImages = $item->images ?? [];
            $data['images'] = array_merge($existingImages, $newImages);
        }

        if (empty($item->item_code)) {
            $lastItemCode = Item::where('company_id', $item->company_id)->max('item_code');
            $data['item_code'] = $lastItemCode ? $lastItemCode + 1 : 1;
        }

        $item->update($data);

        return response()->json([
            'message' => 'Item updated successfully.',
            'item'    => $item,
        ]);
    }

    /**
     * Remove the specified item from storage.
     */
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

    /**
     * Store bulk items from a JSON request.
     */
    public function storeBulkItems(Request $request): JsonResponse
    {
        $selectedCompany = SelectedCompanyService::getSelectedCompanyOrFail();

        $validator = Validator::make($request->all(), [
            'invoice_no'   => 'required|string|max:255',
            'vendor_name'  => 'required|string|max:255',
            'vendor_no'    => 'required|string|max:255',
            'bill_photo'   => 'nullable',
            'items'        => 'required|json',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation errors.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();

        $vendor = StoreVendor::firstOrCreate([
            'vendor_name' => $data['vendor_name'],
            'company_id'  => $selectedCompany->company_id,
        ], [
            'vendor_no'   => $data['vendor_no'],
        ]);

        $imagePath = null;
        if ($request->hasFile('bill_photo')) {
            $image = $request->file('bill_photo');
            $filename = uniqid('bill_') . '.' . $image->getClientOriginalExtension();
            $image->move(public_path('uploads/bills'), $filename);
            $imagePath = 'uploads/bills/' . $filename;
        }

        $items = json_decode($data['items'], true);

        foreach ($items as $itemData) {
            Item::create([
                'company_id'         => $selectedCompany->company_id,
                'item_code'          => Item::where('company_id', $selectedCompany->company_id)->max('item_code') + 1 ?? 1,
                'name'               => $itemData['name'],
                'quantity_count'     => $itemData['quantity'],
                'measurement'        => null,
                'purchase_date'      => now(),
                'date_of_manufacture'=> now(),
                'brand_name'         => $data['vendor_name'],
                'replacement'        => null,
                'category'           => null,
                'vendor_name'        => $data['vendor_name'],
                'availability_stock' => $itemData['quantity'],
                'images'             => $imagePath ? json_encode([$imagePath]) : null,
            ]);
        }

        return response()->json([
            'message' => 'Bulk items stored successfully.',
            'vendor'  => $vendor->vendor_name,
            'count'   => count($items),
        ]);
    }
}
