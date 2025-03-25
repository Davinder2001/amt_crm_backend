<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Attendance;
use Carbon\Carbon;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class AttendanceController extends Controller
{
    /**
     * Record attendance for the current day.
     */
    public function recordAttendance(Request $request): JsonResponse
    {
        $user = $request->user();
        $company = $user->companies()->first();

        if (!$company) {
            return response()->json([
                'message' => 'User is not associated with any company.'
            ], 422);
        }

        $today = Carbon::today()->toDateString();

        $attendance = Attendance::firstOrNew([
            'user_id'         => $user->id,
            'attendance_date' => $today,
        ]);

        if (!$attendance->exists) {
             $validator = Validator::make($request->all(), [
                'image' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Validation errors.',
                    'errors'  => $validator->errors(),
                ], 422);
            }

            $clockInImagePath = $request->file('image')->store('attendance_images', 'public');

            $attendance->company_id     = $company->id;
            $attendance->clock_in       = Carbon::now();
            $attendance->clock_in_image = $clockInImagePath;
            $attendance->status         = 'present';
            $attendance->save();

            return response()->json([
                'message'    => 'Clocked in successfully.',
                'attendance' => $attendance,
            ]);
        }

        if ($attendance->clock_in && !$attendance->clock_out) {
 
            $validator = Validator::make($request->all(), [
                'image' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Validation errors.',
                    'errors'  => $validator->errors(),
                ], 422);
            }

            $clockOutImagePath = $request->file('image')->store('attendance_images', 'public');

            $attendance->clock_out      = Carbon::now();
            $attendance->clock_out_image = $clockOutImagePath;
            $attendance->save();

            return response()->json([
                'message'    => 'Clocked out successfully.',
                'attendance' => $attendance,
            ]);
        }

        return response()->json([
            'message' => 'Attendance already recorded for today.'
        ], 422);
    }
    
    /**
     * Retrieve attendance records for the logged-in user.
     */
    public function getAttendance(Request $request, $id): JsonResponse
    {
        $user = User::findOrFail($id);
        $date = $request->query('date');

        if ($date) {
            $attendance = Attendance::where('user_id', $user->id)->where('attendance_date', $date)->first();

            if (!$attendance) {
                return response()->json([
                    'message' => 'No attendance found for the specified date.'
                ], 404);
            }

            return response()->json([
                'attendance' => $attendance
            ], 200);
        }

        $attendances = Attendance::where('user_id', $user->id)
            ->orderBy('attendance_date', 'desc')
            ->get();

        return response()->json([
            'attendances' => $attendances
        ], 200);
    }

    public function getAllAttendance(Request $request): JsonResponse
    {

        $date = $request->query('date');

        if ($date) {
            $attendances = Attendance::with('user')->where('attendance_date', $date)->orderBy('user_id')->get();
        } else {
            $attendances = Attendance::with('user')
                ->orderBy('attendance_date', 'desc')
                ->get();
        }

        return response()->json([
            'attendances' => $attendances
        ], 200);
    }
}
