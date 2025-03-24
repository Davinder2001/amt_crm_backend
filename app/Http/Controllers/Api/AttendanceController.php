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
     *
     * First request creates a new entry with clock-in time and image.
     * Second request updates that entry with clock-out time and image.
     * Further requests are not allowed.
     */
    public function recordAttendance(Request $request): JsonResponse
    {
        $user = $request->user();

        // Retrieve the first company associated with the user.
        $company = $user->companies()->first();

        if (!$company) {
            return response()->json([
                'message' => 'User is not associated with any company.'
            ], 422);
        }

        $today = Carbon::today()->toDateString();

        // Try to retrieve today's attendance record or create a new one.
        $attendance = Attendance::firstOrNew([
            'user_id'         => $user->id,
            'attendance_date' => $today,
        ]);

        // If the attendance record is new, handle clock-in.
        if (!$attendance->exists) {
            // Validate the image for clock-in.
            $validator = Validator::make($request->all(), [
                'image' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Validation errors.',
                    'errors'  => $validator->errors(),
                ], 422);
            }

            // Store the image (on the public disk under "attendance_images").
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

        // If clock-in exists but clock-out is not set, handle clock-out.
        if ($attendance->clock_in && !$attendance->clock_out) {
            // Validate the image for clock-out.
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

        // If both clock-in and clock-out exist, attendance for today is complete.
        return response()->json([
            'message' => 'Attendance already recorded for today.'
        ], 422);
    }
    
    /**
     * Retrieve attendance records for the logged-in user.
     *
     * Optionally, pass a query parameter "date" (Y-m-d format) to get attendance for a specific date.
     * If no date is provided, returns all attendance records for the user.
     */
    public function getAttendance(Request $request, $id): JsonResponse
    {

        // dd('there');
        // Retrieve the user by the provided id
        $user = User::findOrFail($id);

        // Check for an optional date query parameter.
        $date = $request->query('date'); // e.g., 2025-03-24

        if ($date) {
            $attendance = Attendance::where('user_id', $user->id)
                ->where('attendance_date', $date)
                ->first();

            if (!$attendance) {
                return response()->json([
                    'message' => 'No attendance found for the specified date.'
                ], 404);
            }

            return response()->json([
                'attendance' => $attendance
            ], 200);
        }

        // If no date is provided, return all attendance records for the user.
        $attendances = Attendance::where('user_id', $user->id)
            ->orderBy('attendance_date', 'desc')
            ->get();

        return response()->json([
            'attendances' => $attendances
        ], 200);
    }

}
