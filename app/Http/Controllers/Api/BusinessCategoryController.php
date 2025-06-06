<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\BusinessCategory;

class BusinessCategoryController extends Controller
{
    public function index()
    {
        return response()->json(BusinessCategory::all());
    }



    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $category = BusinessCategory::create([
            'name' => $request->name,
        ]);

        return response()->json([
            'message' => 'Business category created successfully.',
            'data' => $category
        ], 201);
    }


    public function show($id)
    {
        $category = BusinessCategory::find($id);
        if (!$category) {
            return response()->json(['message' => 'Business category not found.'], 404);
        }

        return response()->json($category);
    }

    public function update(Request $request, $id)
    {
        $category = BusinessCategory::find($id);
        if (!$category) {
            return response()->json(['message' => 'Business category not found.'], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $category->update(['name' => $request->name]);

        return response()->json([
            'message' => 'Business category updated successfully.',
            'data' => $category
        ]);
    }


    public function destroy($id)
    {
        $category = BusinessCategory::find($id);
        if (!$category) {
            return response()->json(['message' => 'Business category not found.'], 404);
        }

        $category->delete();

        return response()->json(['message' => 'Business category deleted successfully.']);
    }
}
