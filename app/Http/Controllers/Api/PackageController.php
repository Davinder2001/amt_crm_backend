<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Package;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;

class PackageController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $packages = Package::with('businessCategories')->get();
        return response()->json($packages);
    }



    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'                      => 'required|string|max:255',
            'employee_numbers'          => 'required|integer|min:0',
            'items_number'              => 'required|integer|min:0',
            'daily_tasks_number'        => 'required|integer|min:0',
            'invoices_number'           => 'required|integer|min:0',
            'price'                     => 'required|numeric|min:0',
            'business_category_ids'     => 'required|array',
            'package_type'              => 'required|string|max:255',
            'business_category_ids.*'   => 'integer|exists:business_categories,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();
        
        $package = Package::create([
            'name'                  => $data['name'],
            'employee_numbers'      => $data['employee_numbers'],
            'items_number'          => $data['items_number'],
            'daily_tasks_number'    => $data['daily_tasks_number'],
            'invoices_number'       => $data['invoices_number'],
            'package_type'          => $data['package_type'],
            'price'                 => $data['price'],
        ]);

        $package->businessCategories()->sync($data['business_category_ids']);

        return response()->json([
            'status'    => true,
            'data'      => $package->load('businessCategories'),
            'message'   => 'Package created successfully.',
        ], 201);
    }



    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $package = Package::with('businessCategories')->findOrFail($id);
        return response()->json($package);
    }


    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $validator = Validator::make($request->all(), [
            'name'                      => 'sometimes|string|max:255',
            'employee_numbers'          => 'sometimes|integer|min:0',
            'items_number'              => 'sometimes|integer|min:0',
            'daily_tasks_number'        => 'sometimes|integer|min:0',
            'invoices_number'           => 'sometimes|integer|min:0',
            'price'                     => 'sometimes|numeric|min:0',
            'business_category_ids'     => 'sometimes|array',
            'package_type'                      => 'sometimes|string|max:255',
            'business_category_ids.*'   => 'integer|exists:business_categories,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $package = Package::findOrFail($id);
        $data = $validator->validated();
        $package->update($data);

        if (isset($data['business_category_ids'])) {
            $package->businessCategories()->sync($data['business_category_ids']);
        }

        return response()->json([
            'status'    => true,
            'data'      => $package->load('businessCategories'),
            'message'   => 'Package updated successfully.',
        ]);
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $package = Package::findOrFail($id);
        $package->delete();
        return response()->json(['message' => 'Package deleted successfully']);
    }
}
