<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Package;
use App\Models\PackageLimit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PackageController extends Controller
{
    /**
     * Display a listing of the packages.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $packages = Package::with(['businessCategories', 'limits'])->get();
        return response()->json($packages);
    }

    /**
     * Store a newly created package in storage.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'                      => 'required|string|max:255',
            'package_type'              => 'required|in:general,specific',
            'user_id'                   => 'required_if:package_type,specific|nullable|exists:users,id',
            'monthly_price'             => 'required|numeric|min:0',
            'annual_price'              => 'required|numeric|min:0',
            'three_years_price'         => 'required|numeric|min:0',

            'monthly_limits'            => 'required|array',
            'annual_limits'             => 'required|array',
            'three_years_limits'        => 'required|array',

            'monthly_limits.employee_numbers'     => 'required|integer|min:0',
            'monthly_limits.items_number'         => 'required|integer|min:0',
            'monthly_limits.daily_tasks_number'   => 'required|integer|min:0',
            'monthly_limits.invoices_number'      => 'required|integer|min:0',
            'monthly_limits.task'                 => 'required|boolean',
            'monthly_limits.chat'                 => 'required|boolean',
            'monthly_limits.hr'                   => 'required|boolean',

            'annual_limits.employee_numbers'      => 'required|integer|min:0',
            'annual_limits.items_number'          => 'required|integer|min:0',
            'annual_limits.daily_tasks_number'    => 'required|integer|min:0',
            'annual_limits.invoices_number'       => 'required|integer|min:0',
            'annual_limits.task'                  => 'required|boolean',
            'annual_limits.chat'                  => 'required|boolean',
            'annual_limits.hr'                    => 'required|boolean',

            'three_years_limits.employee_numbers'     => 'required|integer|min:0',
            'three_years_limits.items_number'         => 'required|integer|min:0',
            'three_years_limits.daily_tasks_number'   => 'required|integer|min:0',
            'three_years_limits.invoices_number'      => 'required|integer|min:0',
            'three_years_limits.task'                 => 'required|boolean',
            'three_years_limits.chat'                 => 'required|boolean',
            'three_years_limits.hr'                   => 'required|boolean',

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
            'monthly_price'     => $data['monthly_price'],
            'annual_price'      => $data['annual_price'],
            'three_years_price' => $data['three_years_price'],
        ]);

        $package->businessCategories()->sync($data['business_category_ids']);

        foreach (['monthly', 'annual', 'three_years'] as $variant) {
            $limits = $data["{$variant}_limits"];
            PackageLimit::create([
                'package_id'         => $package->id,
                'variant_type'       => $variant,
                'employee_numbers'   => $limits['employee_numbers'],
                'items_number'       => $limits['items_number'],
                'daily_tasks_number' => $limits['daily_tasks_number'],
                'invoices_number'    => $limits['invoices_number'],
                'task'               => $limits['task'],
                'chat'               => $limits['chat'],
                'hr'                 => $limits['hr'],
            ]);
        }

        return response()->json([
            'status'  => true,
            'data'    => $package->load(['businessCategories', 'limits']),
            'message' => 'Package created successfully.',
        ], 201);
    }

    /**
     * Display the specified package.
     *
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(string $id)
    {
        $package = Package::with(['businessCategories', 'limits'])->findOrFail($id);
        return response()->json($package);
    }

    /**
     * Update the specified package in storage.
     *
     * @param Request $request
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, string $id)
    {
        $validator = Validator::make($request->all(), [
            'name'                      => 'sometimes|string|max:255',
            'package_type'              => 'sometimes|in:general,specific',
            'user_id'                   => 'required_if:package_type,specific|nullable|exists:users,id',
            'monthly_price'             => 'sometimes|numeric|min:0',
            'annual_price'              => 'sometimes|numeric|min:0',
            'three_years_price'         => 'sometimes|numeric|min:0',
            'business_category_ids'     => 'sometimes|array',
            'business_category_ids.*'   => 'integer|exists:business_categories,id',

            'monthly_limits'            => 'sometimes|array',
            'annual_limits'             => 'sometimes|array',
            'three_years_limits'        => 'sometimes|array',
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
            'monthly_price'     => $data['monthly_price'] ?? $package->monthly_price,
            'annual_price'      => $data['annual_price'] ?? $package->annual_price,
            'three_years_price' => $data['three_years_price'] ?? $package->three_years_price,
        ]);

        if (isset($data['business_category_ids'])) {
            $package->businessCategories()->sync($data['business_category_ids']);
        }

        foreach (['monthly', 'annual', 'three_years'] as $variant) {
            if (isset($data["{$variant}_limits"])) {
                $limits = $data["{$variant}_limits"];
                $limitRecord = $package->limits()->where('variant_type', $variant)->first();

                $limitData = [
                    'employee_numbers'   => $limits['employee_numbers'],
                    'items_number'       => $limits['items_number'],
                    'daily_tasks_number' => $limits['daily_tasks_number'],
                    'invoices_number'    => $limits['invoices_number'],
                    'task'               => $limits['task'],
                    'chat'               => $limits['chat'],
                    'hr'                 => $limits['hr'],
                ];

                if ($limitRecord) {
                    $limitRecord->update($limitData);
                } else {
                    PackageLimit::create(array_merge($limitData, [
                        'package_id'   => $package->id,
                        'variant_type' => $variant,
                    ]));
                }
            }
        }

        return response()->json([
            'status'  => true,
            'data'    => $package->load(['businessCategories', 'limits']),
            'message' => 'Package updated successfully.',
        ]);
    }

    /**
     * Remove the specified package from storage.
     *
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(string $id)
    {
        $package = Package::findOrFail($id);
        $package->limits()->delete();
        $package->businessCategories()->detach();
        $package->delete();

        return response()->json([
            'status'  => true,
            'message' => 'Package deleted successfully.',
        ]);
    }
}
