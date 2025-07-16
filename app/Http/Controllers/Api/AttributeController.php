<?php

namespace App\Http\Controllers\Api;

use App\Models\Attribute;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use App\Services\SelectedCompanyService;

class AttributeController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     */
    public function index(): JsonResponse
    {
        $attributes = Attribute::with('values')->get();

        $response = response()->json([
            'status'  => true,
            'message' => 'Attributes retrieved successfully.',
            'data'    => $attributes
        ]);

        Log::info('Attributes list returned.', ['count' => $attributes->count()]);
        return $response;
    }

    /**
     * Get variations with their values.
     *
     */
    public function variations(): JsonResponse
    {
        $attributes = Attribute::with('values')->where('status', 'active')->get();

        $response = response()->json([
            'status'  => true,
            'message' => 'Active attributes retrieved successfully.',
            'data'    => $attributes
        ]);

        Log::info('Active attributes retrieved.', ['count' => $attributes->count()]);
        return $response;
    }

    /**
     * Store a newly created resource in storage.
     *
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name'       => 'required|string|max:255',
            'values'     => 'nullable|array',
            'values.*'   => 'required|string|max:255'
        ]);

        $company = SelectedCompanyService::getSelectedCompanyOrFail();
        $company_id = $company->company->id;

        if ($validator->fails()) {
            $response = response()->json([
                'status'  => false,
                'message' => 'Validation failed.',
                'errors'  => $validator->errors()
            ], 422);

            Log::warning('Attribute validation failed.', ['errors' => $validator->errors()]);
            return $response;
        }

        $attribute = Attribute::create([
            'name'       => $request->name,
            'company_id' => $company_id,
        ]);

        if ($request->has('values')) {
            foreach ($request->values as $value) {
                $attribute->values()->create(['value' => $value]);
            }
        }

        $response = response()->json([
            'status'  => true,
            'message' => 'Attribute created successfully.',
            'data'    => $attribute->load('values')
        ]);

        Log::info('Attribute created.', ['attribute_id' => $attribute->id]);
        return $response;
    }

    /**
     * Display the specified resource.
     *
     */
    public function show($id): JsonResponse
    {
        $attribute = Attribute::with('values')->find($id);

        if (!$attribute) {
            $response = response()->json([
                'status'  => false,
                'message' => 'Attribute not found.'
            ], 404);

            Log::warning('Attempted to view non-existent attribute.', ['id' => $id]);
            return $response;
        }

        $response = response()->json([
            'status'  => true,
            'message' => 'Attribute retrieved successfully.',
            'data'    => $attribute
        ]);

        Log::info('Attribute retrieved.', ['id' => $attribute->id]);
        return $response;
    }

    /**
     * Update the specified resource in storage.
     *
     */
    public function update(Request $request, $id): JsonResponse
    {
        $attribute = Attribute::find($id);

        if (!$attribute) {
            $response = response()->json([
                'status'  => false,
                'message' => 'Attribute not found.'
            ], 404);

            Log::warning('Update failed. Attribute not found.', ['id' => $id]);
            return $response;
        }

        $validator = Validator::make($request->all(), [
            'name'       => 'sometimes|required|string|max:255',
            'values'     => 'nullable|array',
            'values.*'   => 'required|string|max:255'
        ]);

        if ($validator->fails()) {
            $response = response()->json([
                'status'  => false,
                'message' => 'Validation failed.',
                'errors'  => $validator->errors()
            ], 422);

            Log::warning('Attribute update validation failed.', ['errors' => $validator->errors()]);
            return $response;
        }

        if ($request->has('name')) {
            $attribute->update(['name' => $request->name]);
        }

        if ($request->has('values')) {
            $attribute->values()->delete();
            foreach ($request->values as $value) {
                $attribute->values()->create(['value' => $value]);
            }
        }

        $response = response()->json([
            'status'  => true,
            'message' => 'Attribute updated successfully.',
            'data'    => $attribute->load('values')
        ]);

        Log::info('Attribute updated.', ['id' => $attribute->id]);
        return $response;
    }

    /**
     * Remove the specified resource from storage.
     *
     */
    public function destroy($id): JsonResponse
    {
        $attribute = Attribute::find($id);

        if (!$attribute) {
            $response = response()->json([
                'status'  => false,
                'message' => 'Attribute not found.'
            ], 404);

            Log::warning('Delete failed. Attribute not found.', ['id' => $id]);
            return $response;
        }

        $attribute->values()->delete();
        $attribute->delete();

        $response = response()->json([
            'status'  => true,
            'message' => 'Attribute deleted successfully.'
        ]);

        Log::info('Attribute deleted.', ['id' => $id]);
        return $response;
    }

    /**
     * Toggle the status of the specified resource.
     *
     */
    public function toggleStatus($id): JsonResponse
    {
        $attribute = Attribute::find($id);

        if (!$attribute) {
            $response = response()->json([
                'status'  => false,
                'message' => 'Attribute not found.'
            ], 404);

            Log::warning('Toggle status failed. Attribute not found.', ['id' => $id]);
            return $response;
        }

        $attribute->status = $attribute->status === 'active' ? 'inactive' : 'active';
        $attribute->save();

        $response = response()->json([
            'status'  => true,
            'message' => 'Attribute status updated successfully.',
            'data'    => ['status' => $attribute->status]
        ]);

        Log::info('Attribute status toggled.', ['id' => $attribute->id, 'status' => $attribute->status]);
        return $response;
    }
}
