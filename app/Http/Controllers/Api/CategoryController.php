<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\Category;
use Illuminate\Support\Facades\Validator;
use App\Services\SelectedCompanyService;
use App\Http\Resources\CategoryResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;


class CategoryController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $selectedCompany = SelectedCompanyService::getSelectedCompanyOrFail();
        $categories = Category::where('company_id', $selectedCompany->company_id)->get();
    
        return CategoryResource::collection($categories);
    }
    

    public function store(Request $request): JsonResponse
    {
        $selectedCompany = SelectedCompanyService::getSelectedCompanyOrFail();

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:categories,name,NULL,id,company_id,' . $selectedCompany->company_id,
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $category = Category::create([
            'company_id' => $selectedCompany->company_id,
            'name' => $request->name,
        ]);

        return response()->json(['message' => 'Category created.', 'category' => $category], 201);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $selectedCompany = SelectedCompanyService::getSelectedCompanyOrFail();
        $category = Category::where('company_id', $selectedCompany->company_id)->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:categories,name,' . $id . ',id,company_id,' . $selectedCompany->company_id,
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $category->update(['name' => $request->name]);

        return response()->json([
            'message'  => 'Category created.',
            'category' => new CategoryResource($category),
        ], 201);
        
    }

    public function destroy($id): JsonResponse
    {
        $selectedCompany = SelectedCompanyService::getSelectedCompanyOrFail();
        $category = Category::where('company_id', $selectedCompany->company_id)->findOrFail($id);
        $category->delete();

        return response()->json([
            'message'  => 'Category created.',
            'category' => new CategoryResource($category),
        ], 201);
            }

    public function show($id): JsonResponse
    {
        $selectedCompany = SelectedCompanyService::getSelectedCompanyOrFail();
        $category = Category::where('company_id', $selectedCompany->company_id)->findOrFail($id);

        return response()->json([
            'message'  => 'Category created.',
            'category' => new CategoryResource($category),
        ], 201);
            }
}
