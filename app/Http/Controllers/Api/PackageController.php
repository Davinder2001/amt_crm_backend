<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Package;
use App\Models\PackageDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PackageController extends Controller
{
    /**
     * Display a listing of the packages.
     */
    public function index()
    {
        $packages   = Package::with(['businessCategories'])->get();
        $details    = PackageDetail::all();

        $packages->each(function ($package) use ($details) {
            $package->details = $details;
        });

        return response()->json($packages);
    }


    /**
     * Store a newly created package in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'                      => 'required|string|max:255',
            'package_type'              => 'required|in:general,specific',
            'user_id'                   => 'required_if:package_type,specific|nullable|exists:users,id',
            'annual_price'              => 'required|numeric|min:0',
            'three_years_price'         => 'required|numeric|min:0',
            'employee_limit'            => 'required|integer|min:0',
            'chat'                      => 'required|boolean',
            'task'                      => 'required|boolean',
            'hr'                        => 'required|boolean',
            'business_category_ids'     => 'required|array',
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
            'name'              => $data['name'],
            'package_type'      => $data['package_type'],
            'user_id'           => $data['user_id'] ?? null,
            'annual_price'      => $data['annual_price'],
            'three_years_price' => $data['three_years_price'],
            'employee_limit'    => $data['employee_limit'],
            'chat'              => $data['chat'],
            'task'              => $data['task'],
            'hr'                => $data['hr'],
        ]);

        $package->businessCategories()->sync($data['business_category_ids']);

        return response()->json([
            'status'  => true,
            'data'    => $package->load('businessCategories'),
            'message' => 'Package created successfully.',
        ], 201);
    }


    /**
     * Display the specified package.
     */
    public function show(string $id)
    {
        $package = Package::with(['businessCategories'])->findOrFail($id);

        return response()->json($package);
    }

    /**
     * Update the specified package in storage.
     */
    public function update(Request $request, string $id)
    {
        $validator = Validator::make($request->all(), [
            'name'                    => 'sometimes|string|max:255',
            'package_type'            => 'sometimes|in:general,specific',
            'user_id'                 => 'required_if:package_type,specific|nullable|exists:users,id',
            'annual_price'            => 'sometimes|numeric|min:0',
            'three_years_price'       => 'sometimes|numeric|min:0',
            'employee_limit'          => 'sometimes|integer|min:0',
            'chat'                    => 'sometimes|boolean',
            'task'                    => 'sometimes|boolean',
            'hr'                      => 'sometimes|boolean',
            'business_category_ids'   => 'sometimes|array',
            'business_category_ids.*' => 'integer|exists:business_categories,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();
        $package = Package::findOrFail($id);

        $package->update([
            'name'              => $data['name'] ?? $package->name,
            'package_type'      => $data['package_type'] ?? $package->package_type,
            'user_id'           => $data['user_id'] ?? $package->user_id,
            'annual_price'      => $data['annual_price'] ?? $package->annual_price,
            'three_years_price' => $data['three_years_price'] ?? $package->three_years_price,
            'employee_limit'    => $data['employee_limit'] ?? $package->employee_limit,
            'chat'              => array_key_exists('chat', $data) ? $data['chat'] : $package->chat,
            'task'              => array_key_exists('task', $data) ? $data['task'] : $package->task,
            'hr'                => array_key_exists('hr', $data) ? $data['hr'] : $package->hr,
        ]);

        if (isset($data['business_category_ids'])) {
            $package->businessCategories()->sync($data['business_category_ids']);
        }

        return response()->json([
            'status'  => true,
            'data'    => $package->load('businessCategories'),
            'message' => 'Package updated successfully.',
        ]);
    }


    /**
     * Remove the specified package from storage.
     */
    public function destroy(string $id)
    {
        $package = Package::findOrFail($id);

        $package->businessCategories()->detach();
        $package->delete();

        return response()->json([
            'status'  => true,
            'message' => 'Package deleted successfully.',
        ]);
    }
}
