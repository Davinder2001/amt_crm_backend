<?php

namespace App\Http\Controllers\API;

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
        $packages = Package::all();
        return response()->json($packages);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'employee_numbers' => 'required|integer|min:0',
            'items_number' => 'required|integer|min:0',
            'daily_tasks_number' => 'required|integer|min:0',
            'invoices_number' => 'required|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $package = Package::create($validator->validated());

        return response()->json([
            'status' => true,
            'data' => $package,
            'message' => 'Package created successfully.',
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $package = Package::findOrFail($id);
        return response()->json($package);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'employee_numbers' => 'sometimes|integer|min:0',
            'items_number' => 'sometimes|integer|min:0',
            'daily_tasks_number' => 'sometimes|integer|min:0',
            'invoices_number' => 'sometimes|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $package = Package::findOrFail($id);
        $package->update($validator->validated());

        return response()->json([
            'status' => true,
            'data' => $package,
            'message' => 'Package updated successfully.',
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
