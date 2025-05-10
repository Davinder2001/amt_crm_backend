<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Shift;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Services\SelectedCompanyService;

class ShiftsController extends Controller
{
    protected $selectcompanyService;

    public function __construct(SelectedCompanyService $selectcompanyService)
    {
        $this->selectcompanyService = $selectcompanyService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $shifts = Shift::all();

        return response()->json([
            'message' => 'Shifts retrieved successfully.',
            'data'    => $shifts
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'shift_name'      => 'required|string',
            'start_time'      => 'required|date_format:H:i',
            'end_time'        => 'required|date_format:H:i|after:start_time',
            'weekly_off_day'  => 'required|string|in:Sunday,Monday,Tuesday,Wednesday,Thursday,Friday,Saturday',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors'  => $validator->errors()
            ], 422);
        }

        $company = $this->selectcompanyService->getSelectedCompanyOrFail();

        Shift::create([
            'company_id'      => $company->company_id,
            'shift_name'      => $request->shift_name,
            'start_time'      => $request->start_time,
            'end_time'        => $request->end_time,
            'weekly_off_day'  => $request->weekly_off_day,
        ]);

        return response()->json([
            'message' => 'Shift created successfully.'
        ], 201);
    }

    /**
     * Show the specified resource.
     */
    public function show($id)
    {
        $shift = Shift::find($id);

        if (!$shift) {
            return response()->json([
                'message' => 'Shift not found.'
            ], 404);
        }

        return response()->json([
            'message' => 'Shift retrieved successfully.',
            'data'    => $shift
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $shift = Shift::find($id);

        if (!$shift) {
            return response()->json([
                'message' => 'Shift not found.'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'shift_name'      => 'required|string',
            'start_time'      => 'required|date_format:H:i',
            'end_time'        => 'required|date_format:H:i|after:start_time',
            'weekly_off_day'  => 'required|string|in:Sunday,Monday,Tuesday,Wednesday,Thursday,Friday,Saturday',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors'  => $validator->errors()
            ], 422);
        }

        $shift->update([
            'shift_name'      => $request->shift_name,
            'start_time'      => $request->start_time,
            'end_time'        => $request->end_time,
            'weekly_off_day'  => $request->weekly_off_day,
        ]);

        return response()->json([
            'message' => 'Shift updated successfully.'
        ], 200);
    }
}
