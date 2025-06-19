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
    public function index(): JsonResponse
    {
        // $company = SelectedCompanyService::getSelectedCompanyOrFail()->company_id;
        // $companyId = $company->company->id;
        // $units = MeasuringUnit::where('company_id', $companyId)->get();
        $units = MeasuringUnit::get();

        return response()->json([
            'units' => $units
        ], 200);
    }

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

    public function show(int $id): JsonResponse
    {
        $unit = MeasuringUnit::find($id);
        if (!$unit) {
            return response()->json(['message' => 'Unit not found.'], 404);
        }

        return response()->json(['unit' => $unit], 200);
    }

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
