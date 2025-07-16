<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use App\Models\MeasuringUnit;
use App\Services\SelectedCompanyService;

class MeasuringUnitController extends Controller
{
    /**
     * Display a listing of the measuring units.
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $units = MeasuringUnit::get();

        return response()->json([
            'units' => $units
        ], 200);
    }

    /**
     * Store a newly created measuring unit in storage.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $company = SelectedCompanyService::getSelectedCompanyOrFail();
        $companyId = $company->company->id;

        $unit = MeasuringUnit::create([
            'name' => $request->name,
            'company_id' => $companyId,
        ]);

        return response()->json(['message' => 'Measuring unit created.', 'unit' => $unit], 201);
    }

    /**
     * Display the specified measuring unit.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        $unit = MeasuringUnit::find($id);
        if (!$unit) {
            return response()->json(['message' => 'Unit not found.'], 404);
        }

        return response()->json(['unit' => $unit], 200);
    }

    /**
     * Update the specified measuring unit in storage.
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $unit = MeasuringUnit::find($id);
        if (!$unit) {
            return response()->json(['message' => 'Unit not found.'], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $company = SelectedCompanyService::getSelectedCompanyOrFail();
        $companyId = $company->company->id;

        $unit->update([
            'name' => $request->name,
            'company_id' => $companyId,
        ]);

        return response()->json(['message' => 'Measuring unit updated.', 'unit' => $unit], 200);
    }

    /**
     * Remove the specified measuring unit from storage.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        $unit = MeasuringUnit::find($id);
        if (!$unit) {
            return response()->json([
                'message' => 'Unit not found.'
            ], 404);
        }

        $unit->delete();
        return response()->json([
            'message' => 'Measuring unit deleted.'
        ], 200);
    }
}
