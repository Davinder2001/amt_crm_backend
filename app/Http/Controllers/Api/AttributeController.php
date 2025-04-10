<?php

namespace App\Http\Controllers\Api;

use App\Models\Attribute;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class AttributeController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(Attribute::with('values')->get());
    }


    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'values' => 'nullable|array',
            'values.*' => 'required|string|max:255'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $attribute = Attribute::create(['name' => $request->name]);

        if ($request->has('values')) {
            foreach ($request->values as $value) {
                $attribute->values()->create(['value' => $value]);
            }
        }

        return response()->json(['message' => 'Attribute created', 'data' => $attribute->load('values')]);
    }


    public function show($id): JsonResponse
    {
        $attribute = Attribute::with('values')->find($id);

        if (!$attribute) {
            return response()->json(['message' => 'Attribute not found'], 404);
        }

        return response()->json($attribute);
    }


    public function update(Request $request, $id): JsonResponse
    {
        $attribute = Attribute::find($id);

        if (!$attribute) {
            return response()->json(['message' => 'Attribute not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'values' => 'nullable|array',
            'values.*' => 'required|string|max:255'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
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

        return response()->json(['message' => 'Attribute updated', 'data' => $attribute->load('values')]);
    }

    
    public function destroy($id): JsonResponse
    {
        $attribute = Attribute::find($id);

        if (!$attribute) {
            return response()->json(['message' => 'Attribute not found'], 404);
        }

        $attribute->values()->delete();
        $attribute->delete();

        return response()->json(['message' => 'Attribute deleted']);
    }
}
