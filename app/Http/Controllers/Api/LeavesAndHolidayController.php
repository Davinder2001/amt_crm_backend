<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Leave;
use App\Models\Holiday;
use Carbon\Carbon;
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
            'day'  => 'required|date',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $company         = SelectedCompanyService::getSelectedCompanyOrFail();
        $validated       = $validator->validated();
        $createdHolidays = [];

        $year = now()->year;
        $name = $validated['name'];
        $type = $validated['type'];
        $day  = Carbon::parse($validated['day']);

        if ($type === 'weekly') {

            $weekday = $day->dayOfWeek;
            $start   = Carbon::create($year, 1, 1);
            $end     = Carbon::create($year, 12, 31);

            while ($start->lte($end)) {
                if ($start->dayOfWeek === $weekday) {
                    $createdHolidays[] = Holiday::create([
                        'company_id' => $company->company->id,
                        'name'       => $name,
                        'type'       => $type,
                        'day'        => $start->copy()->toDateString(),
                    ]);
                }
                $start->addDay();
            }
        } elseif ($type === 'monthly') {
            $dayOfMonth = $day->day;

            for ($month = 1; $month <= 12; $month++) {
                try {
                    $date = Carbon::create($year, $month, $dayOfMonth);
                    $createdHolidays[] = Holiday::create([
                        'company_id' => $company->company->id,
                        'name'       => $name,
                        'type'       => $type,
                        'day'        => $date->toDateString(),
                    ]);
                } catch (\Exception $e) {
                    continue;
                }
            }
        } else {

            $createdHolidays[] = Holiday::create([
                'company_id' => $company->company->id,
                'name'       => $name,
                'type'       => $type,
                'day'        => $day->toDateString(),
            ]);
        }

        return response()->json([
            'message' => 'Holiday(s) created successfully.',
            'data'    => HolidayResource::collection($createdHolidays),
        ]);
    }


    public function updateHoliday(Request $request, $id)
    {
        $holiday = Holiday::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'type' => 'sometimes|required|in:monthly,weekly,general',
            'day'  => 'sometimes|required|date',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $validated = $validator->validated();
        $companyId = $holiday->company_id;
        $name      = $validated['name'] ?? $holiday->name;
        $type      = $validated['type'] ?? $holiday->type;
        $day       = isset($validated['day']) ? Carbon::parse($validated['day']) : Carbon::parse($holiday->day);

        $createdHolidays = [];

        if (in_array($type, ['weekly', 'monthly'])) {

            Holiday::where('company_id', $companyId)->where('type', $holiday->type)->where('name', $holiday->name)->delete();

            $year = now()->year;

            if ($type === 'weekly') {
                $weekday = $day->dayOfWeek;

                $start = Carbon::create($year, 1, 1);
                $end   = Carbon::create($year, 12, 31);

                while ($start->lte($end)) {
                    if ($start->dayOfWeek === $weekday) {
                        $createdHolidays[] = Holiday::create([
                            'company_id' => $companyId,
                            'name'       => $name,
                            'type'       => $type,
                            'day'        => $start->copy()->toDateString(),
                        ]);
                    }
                    $start->addDay();
                }
            } elseif ($type === 'monthly') {
                $dayOfMonth = $day->day;

                for ($month = 1; $month <= 12; $month++) {
                    try {
                        $date = Carbon::create($year, $month, $dayOfMonth);
                        $createdHolidays[] = Holiday::create([
                            'company_id' => $companyId,
                            'name'       => $name,
                            'type'       => $type,
                            'day'        => $date->toDateString(),
                        ]);
                    } catch (\Exception $e) {
                        continue;
                    }
                }
            }
        } else {
            $holiday->update([
                'name' => $name,
                'type' => $type,
                'day'  => $day->toDateString(),
            ]);

            $createdHolidays[] = $holiday;
        }

        return response()->json([
            'message' => 'Holiday updated successfully.',
            'data'    => HolidayResource::collection($createdHolidays),
        ]);
    }


    public function deleteHoliday($id)
    {
        $holiday = Holiday::findOrFail($id);
        $holiday->delete();

        return response()->json(['message' => 'Holiday deleted successfully.']);
    }

    // public function bulkDeleteHolidays(Request $request)
    // {
    //     $validator = Validator::make($request->all(), [
    //         'ids' => 'required|array|min:1',
    //         'ids.*' => 'integer|exists:holidays,id',
    //     ]);

    //     if ($validator->fails()) {
    //         return response()->json(['errors' => $validator->errors()], 422);
    //     }

    //     $deletedCount = Holiday::whereIn('id', $request->ids)->delete();

    //     return response()->json([
    //         'message' => "$deletedCount holiday(s) deleted successfully.",
    //     ]);
    // }

    public function bulkDeleteHolidays(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required|in:weekly,monthly,general',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $company = SelectedCompanyService::getSelectedCompanyOrFail();
        $type    = $request->input('type');

        $deletedCount = Holiday::where('company_id', $company->company->id)
            ->where('type', $type)
            ->delete();

        return response()->json([
            'message' => "$deletedCount $type holiday(s) deleted successfully.",
        ]);
    }
}
