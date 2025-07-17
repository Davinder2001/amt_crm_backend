<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\Leave;
use App\Models\LeaveApplication;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use App\Http\Resources\AttendanceResource;
use App\Services\SelectedCompanyService;
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
        $activeCompany = SelectedCompanyService::getSelectedCompanyOrFail();

        if (!$activeCompany) {
            return response()->json([
                'message' => 'User is not associated with any company.'
            ], 422);
        }

        $today = Carbon::today()->toDateString();

        $attendance = Attendance::firstOrNew([
            'user_id'         => $user->id,
            'attendance_date' => $today,
        ]);

        // ✅ Helper function to upload image (returns relative path like attendance_images/filename.jpg)
        $uploadImage = function ($file) {
            $fileName = uniqid('attendance_', true) . '.' . $file->getClientOriginalExtension();
            return $file->storeAs('attendance_images', $fileName, 'public');
        };

        // ✅ Validation logic (used in all cases)
        $validateImage = function () use ($request) {
            $validator = Validator::make($request->all(), [
                'image' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            ]);

            if ($validator->fails()) {
                response()->json([
                    'message' => 'Validation errors.',
                    'errors'  => $validator->errors(),
                ], 422)->send();
                exit; // stop further execution after sending response
            }
        };

        $currentTime = Carbon::now('Asia/Kolkata')->format('h:i A');

        // ✅ Override rejected leave
        if ($attendance->exists && $attendance->status === 'leave' && $attendance->approval_status === 'rejected') {
            $validateImage();

            $attendance->company_id       = $activeCompany->company_id;
            $attendance->clock_in         = $currentTime;
            $attendance->clock_in_image   = $uploadImage($request->file('image'));
            $attendance->status           = 'present';
            $attendance->approval_status  = 'pending';
            $attendance->save();

            return response()->json([
                'message'    => 'Leave overridden. Clocked in successfully.',
                'attendance' => $attendance,
            ]);
        }

        // ✅ New clock-in
        if (!$attendance->exists) {
            $validateImage();

            $attendance->company_id       = $activeCompany->company_id;
            $attendance->clock_in         = $currentTime;
            $attendance->clock_in_image   = $uploadImage($request->file('image'));
            $attendance->status           = 'present';
            $attendance->approval_status  = 'pending';
            $attendance->save();

            return response()->json([
                'message'    => 'Clocked in successfully.',
                'attendance' => $attendance,
            ]);
        }

        // ✅ Clock-out
        if ($attendance->clock_in && !$attendance->clock_out) {
            $validateImage();

            $attendance->clock_out       = $currentTime;
            $attendance->clock_out_image = $uploadImage($request->file('image'));
            $attendance->save();

            return response()->json([
                'message'    => 'Clocked out successfully.',
                'attendance' => $attendance,
            ]);
        }

        return response()->json([
            'message' => 'Attendance already recorded for today.',
            'date'    => $today,
        ], 422);
    }


    /**
     * Applay for leave current day.
     */
    public function applyForLeave(Request $request): JsonResponse
    {
        $user = $request->user();
        $activeCompany = SelectedCompanyService::getSelectedCompanyOrFail();

        if (!$activeCompany) {
            return response()->json([
                'message' => 'User is not associated with any company.',
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'dates' => 'required|array|min:1',
            'dates.*' => 'required|date|date_format:Y-m-d',
            'leave_type' => 'required|integer|exists:leaves,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();
        $leave = Leave::find($validated['leave_type']);

        if (!$leave) {
            return response()->json([
                'message' => 'Leave type not found.',
            ], 404);
        }

        $leaveQuery = LeaveApplication::where('user_id', $user->id)
            ->where('leave_id', $leave->id)
            ->where('company_id', $activeCompany->company_id);

        if ($leave->frequency === 'monthly') {
            $leaveQuery->whereMonth('leave_date', now()->month)
                ->whereYear('leave_date', now()->year);
        } elseif ($leave->frequency === 'yearly') {
            $leaveQuery->whereYear('leave_date', now()->year);
        }

        $usedLeaveCount      = $leaveQuery->count();
        $availableLeaves     = $leave->count - $usedLeaveCount;
        $requestedLeaveCount = count($validated['dates']);

        if ($availableLeaves <= 0) {
            return response()->json([
                'message' => "No {$leave->name} leaves remaining.",
            ], 422);
        }

        if ($requestedLeaveCount > $availableLeaves) {
            return response()->json([
                'message' => "Only {$availableLeaves} {$leave->name} leave(s) remaining. You requested {$requestedLeaveCount}.",
            ], 422);
        }

        $appliedLeaves = [];
        $skippedDates = [];

        foreach ($validated['dates'] as $date) {
            $alreadyApplied = LeaveApplication::where('user_id', $user->id)
                ->where('leave_date', $date)
                ->where('leave_id', $leave->id)
                ->exists();

            if ($alreadyApplied) {
                $skippedDates[] = $date;
                continue;
            }

            $attendance = Attendance::firstOrCreate(
                [
                    'user_id' => $user->id,
                    'attendance_date' => $date,
                ],
                [
                    'company_id' => $activeCompany->company_id,
                    'status' => 'leave',
                    'approval_status' => 'pending',
                    'clock_in' => '-',
                    'clock_out' => '-',
                    'clock_in_image' => null,
                    'clock_out_image' => null,
                ]
            );

            $leaveApp = LeaveApplication::create([
                'user_id' => $user->id,
                'company_id' => $activeCompany->company_id,
                'leave_id' => $leave->id,
                'leave_date' => $date,
                'status' => 'pending',
                'attendance_id' => $attendance->id,
            ]);

            $appliedLeaves[] = $leaveApp;
        }

        return response()->json([
            'message' => 'Leave application processed.',
            'applied' => $appliedLeaves,
            'already_exist' => $skippedDates,
        ]);
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

        $attendances = Attendance::where('user_id', $user->id)->orderBy('attendance_date', 'desc')->get();

        return response()->json([
            'attendance' => new AttendanceResource($attendances),
        ], 200);
    }


    public function myAttendance(Request $request)
    {
        $date       = $request->query('date');
        $authUserId = $request->user()->id;

        if ($date) {
            $attendances = Attendance::with('user')
                ->where('user_id', $authUserId)
                ->where('attendance_date', $date)
                ->orderBy('attendance_date', 'desc')
                ->get();
        } else {
            $attendances = Attendance::with('user')
                ->where('user_id', $authUserId)
                ->orderBy('attendance_date', 'desc')
                ->get();
        }

        return response()->json([
            'attendance' => AttendanceResource::collection($attendances),
        ], 200);
    }

    /**
     * Get the attendance.
     */
    public function getAllAttendance(Request $request): JsonResponse
    {
        $date = $request->query('date');

        if ($date) {

            $attendances = Attendance::with('user')->where('attendance_date', $date)->orderBy('user_id')->get();
        } else {

            $attendances = Attendance::with('user')->orderBy('attendance_date', 'desc')->get();
        }

        return response()->json([
            'attendance' => AttendanceResource::collection($attendances),
        ], 200);
    }


    /**
     * Approve the attendance.
     */
    public function approveAttendance($id): JsonResponse
    {
        $attendance = Attendance::find($id);

        if (!$attendance) {
            return response()->json([
                'message' => 'Attendance not found.'
            ], 404);
        }

        $attendance->approval_status = 'approved';
        $attendance->save();

        if ($attendance->status === 'leave') {
            LeaveApplication::where('attendance_id', $attendance->id)
                ->update(['status' => 'approved']);
        }

        return response()->json([
            'message' => 'Attendance approved successfully.',
        ], 200);
    }

    /**
     * Reject the attendance.
     */
    public function rejectAttendance($id): JsonResponse
    {
        $attendance = Attendance::find($id);

        if (!$attendance) {
            return response()->json([
                'message' => 'Attendance not found.'
            ], 404);
        }

        $attendance->approval_status = 'rejected';
        $attendance->save();

        if ($attendance->status === 'leave') {
            LeaveApplication::where('attendance_id', $attendance->id)
                ->update(['status' => 'rejected']);
        }

        return response()->json([
            'message'    => 'Attendance rejected successfully.',
            'attendance' => [
                'id'              => $attendance->id,
                'user_id'         => $attendance->user_id,
                'attendance_date' => $attendance->attendance_date,
                'approval_status' => $attendance->approval_status,
                'status'          => $attendance->status,
                'clock_in'        => $attendance->clock_in,
                'clock_out'       => $attendance->clock_out,
                'clock_in_image'  => $attendance->clock_in_image,
                'clock_out_image' => $attendance->clock_out_image,
            ]
        ], 200);
    }

    /**
     * Get attendance summary by custom date range from request body.
     */
    public function getAttendanceSummary(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'start_date' => 'required|date|date_format:Y-m-d',
            'end_date'   => 'required|date|date_format:Y-m-d|after_or_equal:start_date',
            'mode'       => 'nullable|string|in:range',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $validated  = $validator->validated();
        $start      = Carbon::parse($validated['start_date'])->startOfDay();
        $end        = Carbon::parse($validated['end_date'])->endOfDay();

        $query = Attendance::with('user')
            ->whereBetween('attendance_date', [$start->toDateString(), $end->toDateString()])
            ->orderBy('attendance_date', 'desc');

        $attendances = $query->get();

        return response()->json([
            'mode'        => $validated['mode'] ?? 'range',
            'start_date'  => $start->toDateString(),
            'end_date'    => $end->toDateString(),
            'attendance'  => AttendanceResource::collection($attendances),
        ]);
    }


    /**
     * Get leave balance for the user.
     */
    public function getLeaveBalance(Request $request): JsonResponse
    {
        $user = $request->user();
        $activeCompany = SelectedCompanyService::getSelectedCompanyOrFail();

        if (!$activeCompany) {
            return response()->json([
                'message' => 'User is not associated with any company.',
            ], 422);
        }

        $leaves = Leave::all();
        $results = [];

        foreach ($leaves as $leave) {
            $leaveQuery = LeaveApplication::where('user_id', $user->id)
                ->where('leave_id', $leave->id)
                ->where('company_id', $activeCompany->company_id);

            if ($leave->frequency === 'monthly') {
                $leaveQuery->whereMonth('leave_date', now()->month)
                    ->whereYear('leave_date', now()->year);
            } elseif ($leave->frequency === 'yearly') {
                $leaveQuery->whereYear('leave_date', now()->year);
            }

            $used = $leaveQuery->count();
            $remaining = max(0, $leave->count - $used);

            $results[] = [
                'leave_type'    => $leave->name,
                'leave_id'      => $leave->id,
                'frequency'     => $leave->frequency,
                'total_allowed' => $leave->count,
                'used'          => $used,
                'remaining'     => $remaining,
            ];
        }

        return response()->json([
            'leaves' => $results,
        ]);
    }
}
