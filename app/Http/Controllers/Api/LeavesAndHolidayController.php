<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Leave;
use App\Models\Holiday;
use App\Http\Resources\LeaveResource;
use App\Http\Resources\HolidayResource;
use App\Services\SelectedCompanyService;

class LeavesAndHolidayController extends Controller
{
    // ============================
    // LEAVES CRUD
    // ============================

    public function getLeaves()
    {
        $leaves = Leave::all();
        return LeaveResource::collection($leaves);
    }

    public function createLeave(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'      => 'required|string|max:255',
            'frequency' => 'required|in:monthly,yearly',
            'type'      => 'required|in:paid,unpaid',
            'count'     => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $company = SelectedCompanyService::getSelectedCompanyOrFail();

        $leave = Leave::create([
            'company_id' => $company->company->id,
            'name'       => $request->input('name'),
            'frequency'  => $request->input('frequency'),
            'type'       => $request->input('type'),
            'count'      => $request->input('count'),
        ]);

        return response()->json([
            'message' => 'Leave created successfully.',
            'data'    => new LeaveResource($leave),
        ]);
    }

    public function updateLeave(Request $request, $id)
    {
        $leave = Leave::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name'      => 'sometimes|required|string|max:255',
            'frequency' => 'sometimes|required|in:monthly,yearly',
            'type'      => 'sometimes|required|in:paid,unpaid',
            'count'     => 'sometimes|required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $leave->update([
            'name'      => $request->input('name', $leave->name),
            'frequency' => $request->input('frequency', $leave->frequency),
            'type'      => $request->input('type', $leave->type),
            'count'     => $request->input('count', $leave->count),
        ]);

        return response()->json([
            'message' => 'Leave updated successfully.',
            'data'    => new LeaveResource($leave),
        ]);
    }

    public function deleteLeave($id)
    {
        $leave = Leave::findOrFail($id);
        $leave->delete();

        return response()->json(['message' => 'Leave deleted successfully.']);
    }

    // ============================
    // HOLIDAYS CRUD
    // ============================

    public function getHolidays()
    {
        $company = SelectedCompanyService::getSelectedCompanyOrFail();

        $holidays = Holiday::where('company_id', $company->company->id)->get();

        return HolidayResource::collection($holidays);
    }

    public function createHoliday(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'type' => 'required|in:monthly,weekly,general',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $company = SelectedCompanyService::getSelectedCompanyOrFail();

        $holiday = Holiday::create([
            'company_id' => $company->company->id,
            'name'       => $request->input('name'),
            'type'       => $request->input('type'),
        ]);

        return response()->json([
            'message' => 'Holiday created successfully.',
            'data'    => new HolidayResource($holiday),
        ]);
    }

    public function updateHoliday(Request $request, $id)
    {
        $holiday = Holiday::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'type' => 'sometimes|required|in:monthly,weekly,general',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $holiday->update([
            'name' => $request->input('name', $holiday->name),
            'type' => $request->input('type', $holiday->type),
        ]);

        return response()->json([
            'message' => 'Holiday updated successfully.',
            'data'    => new HolidayResource($holiday),
        ]);
    }

    public function deleteHoliday($id)
    {
        $holiday = Holiday::findOrFail($id);
        $holiday->delete();

        return response()->json(['message' => 'Holiday deleted successfully.']);
    }
}
