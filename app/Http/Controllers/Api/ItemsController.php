<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ItemResource;
use App\Models\Item;
use App\Models\Package;
use App\Services\{ItemService, SelectedCompanyService};
use App\Helpers\ImageHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\JsonResponse;

class ItemsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->get('per_page', 10);

        $items = Item::with([
            'taxes',
            'categories',
            'batches.variants.attributeValues.attribute',
            'batches.vendor',
            'measurementDetails',
            'brand',
            'vendor'
        ])->paginate($perPage);

        return response()->json([
            'status' => true,
            'items' => ItemResource::collection($items->items()), 
            'pagination' => [
                'current_page' => $items->currentPage(),
                'per_page' => $items->perPage(),
                'total' => $items->total(),
                'last_page' => $items->lastPage(),
                'next_page_url' => $items->nextPageUrl(),
                'prev_page_url' => $items->previousPageUrl(),
            ]
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $selectedCompany = SelectedCompanyService::getSelectedCompanyOrFail();
        $company = $selectedCompany->company;
        $companyId = $company->id;

        $validator = Validator::make($request->all(), [
            'name'           => 'required|string|max:255',
            'measurement'    => 'nullable|integer',
            'brand_id'       => 'nullable|integer',
            'tax_id'         => 'nullable|integer|exists:taxes,id',
            'featured_image' => 'nullable|image|mimes:jpg,jpeg,png|max:5120',
            'images'         => 'nullable|array',
            'images.*'       => 'image|mimes:jpg,jpeg,png|max:5120',
            'categories'     => 'nullable|array',
            'categories.*'   => 'integer|exists:categories,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();
        $data['company_id'] = $companyId;


        try {
            DB::beginTransaction();

            $data['item_code'] = ItemService::generateNextItemCode($companyId);
            $data['images'] = ImageHelper::processImages($request->file('images') ?? []);
            $data['featured_image'] = $request->hasFile('featured_image')
                ? ImageHelper::saveImage($request->file('featured_image'), 'featured_')
                : null;

            $item = Item::create($data);

            ItemService::assignCategories($item, $data['categories'] ?? null, $companyId);
            ItemService::assignTax($item, $data['tax_id'] ?? null);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Item created successfully.',
                'item'    => new ItemResource($item->load([
                    'variants.attributeValues.attribute',
                    'categories',
                    'taxes',
                    'batches',
                    'measurementDetails',
                    'brand',
                ]))
            ], 201);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error creating item.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id): JsonResponse
    {
        $item = Item::with([
            'taxes',
            'categories',
            'batches.variants.attributeValues.attribute',
            'batches.vendor',
            'measurementDetails',
            'brand',
            'vendor',
        ])->find($id);

        return $item
            ? response()->json(new ItemResource($item))
            : response()->json(['message' => 'Item not found.'], 404);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name'             => 'sometimes|required|string|max:255',
            'measurement'      => 'sometimes|nullable|integer',
            'brand_id'         => 'sometimes|nullable|integer',
            'tax_id'           => 'sometimes|nullable|integer|exists:taxes,id',
            'featured_image'   => 'sometimes|nullable|image|mimes:jpg,jpeg,png|max:5120',
            'images'           => 'sometimes|array',
            'images.*'         => 'image|mimes:jpg,jpeg,png|max:5120',
            'removed_images'   => 'sometimes|array',
            'removed_images.*' => 'string',
            'categories'       => 'sometimes|nullable|array',
            'categories.*'     => 'integer|exists:categories,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $selectedCompany = SelectedCompanyService::getSelectedCompanyOrFail();
        $companyId = $selectedCompany->company->id;

        $item = Item::with(['variants', 'categories', 'batches'])->find($id);
        if (!$item) {
            return response()->json([
                'success' => false,
                'message' => 'Item not found.'
            ], 404);
        }

        if (!$selectedCompany->super_admin && $item->company_id !== $companyId) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.'
            ], 403);
        }

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

            $item->categories()->sync([]);
            ItemService::assignCategories($item, $data['categories'] ?? null, $companyId);
            ItemService::assignTax($item, $data['tax_id'] ?? null);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Item updated successfully.',
                'item'    => new ItemResource($item->load([
                    'categories',
                    'taxes',
                    'batches.variants.attributeValues.attribute',
                    'measurementDetails',
                    'brand',
                ]))
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error updating item.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
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
        ], 200);
    }
}
