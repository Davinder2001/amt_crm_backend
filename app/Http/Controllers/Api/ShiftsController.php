<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

use App\Models\Shift;
use App\Models\Company;
use Illuminate\Http\Request;

class ShiftsController extends Controller
{
    public function index()
    {
        $shifts = Shift::get();
    
        return response()->json([
            'message' => 'Shifts',
            'shifts' => $shifts
        ], 200);
    }

    public function store(Request $request, Company $company)
    {
        $request->validate([
            'shifts' => 'required|array',
            'shifts.*.shift_name' => 'required|string',
            'shifts.*.start_time' => 'required|date_format:H:i',
            'shifts.*.end_time' => 'required|date_format:H:i|after:shifts.*.start_time',
        ]);

        foreach ($request->shifts as $shiftData) {
            $company->shifts()->create([
                'shift_name' => $shiftData['shift_name'],
                'start_time' => $shiftData['start_time'],
                'end_time' => $shiftData['end_time'],
            ]);
        }

        return response()->json(['message' => 'Shifts created successfully'], 201);
    }

    public function update(Request $request, Company $company)
    {
        $request->validate([
            'shifts' => 'required|array',
            'shifts.*.id' => 'nullable|exists:shifts,id',
            'shifts.*.shift_name' => 'required|string',
            'shifts.*.start_time' => 'required|date_format:H:i',
            'shifts.*.end_time' => 'required|date_format:H:i|after:shifts.*.start_time',
        ]);

        $company->shifts()->delete();

        foreach ($request->shifts as $shiftData) {
            $company->shifts()->create([
                'shift_name' => $shiftData['shift_name'],
                'start_time' => $shiftData['start_time'],
                'end_time' => $shiftData['end_time'],
            ]);
        }

        return response()->json(['message' => 'Shifts updated successfully'], 200);
    }

    public function show(Company $company)
    {
        return response()->json([
            'company' => $company->load('shifts'),
        ]);
    }
}
