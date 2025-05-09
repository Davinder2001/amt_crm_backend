<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Attendance;
use Carbon\Carbon;
use App\Http\Resources\AttendanceResource;
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
        $user       = $request->user();
        $company    = $user->companies()->first();
    
        if (!$company) {
            return response()->json([
                'message' => 'User is not associated with any company.'
            ], 422);
        }
    
        $today      = Carbon::today()->toDateString();
    
        $attendance = Attendance::firstOrNew([
            'user_id'         => $user->id,
            'attendance_date' => $today,
        ]);
    
        if ($attendance->exists && $attendance->status === 'leave' && $attendance->approval_status === 'rejected') {
            $validator  = Validator::make($request->all(), [
                'image' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            ]);
    
            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Validation errors.',
                    'errors'  => $validator->errors(),
                ], 422);
            }
    
            $image      = $request->file('image');
            $imageName  = uniqid('attendance_', true) . '.' . $image->getClientOriginalExtension();
            $image->move(public_path('images/attendance_images'), $imageName);
    
            $clockInImagePath   = 'images/attendance_images/' . $imageName;
            $time               = Carbon::now('Asia/Kolkata')->format('h:i A');
    
            $attendance->company_id       = $company->id;
            $attendance->clock_in         = $time;
            $attendance->clock_in_image   = $clockInImagePath;
            $attendance->status           = 'present';
            $attendance->approval_status  = 'pending'; 
            $attendance->save();
    
            return response()->json([
                'message'    => 'Leave overridden. Clocked in successfully.',
                'attendance' => $attendance,
            ]);
        }
    
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
    
            $image      = $request->file('image');
            $imageName  = uniqid('attendance_', true) . '.' . $image->getClientOriginalExtension();
            $image->move(public_path('images/attendance_images'), $imageName);

            $clockInImagePath = 'images/attendance_images/' . $imageName;
            $time = Carbon::now('Asia/Kolkata')->format('h:i A');
    
            $attendance->company_id       = $company->id;
            $attendance->clock_in         = $time;
            $attendance->clock_in_image   = $clockInImagePath;
            $attendance->status           = 'present';
            $attendance->approval_status  = 'pending';
            $attendance->save();
    
            return response()->json([
                'message'    => 'Clocked in successfully.',
                'attendance' => $attendance,
            ]);
        }
    
        if ($attendance->clock_in && !$attendance->clock_out) {
            $validator  = Validator::make($request->all(), [
                'image' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            ]);
    
            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Validation errors.',
                    'errors'  => $validator->errors(),
                ], 422);
            }
    
            $image      = $request->file('image');
            $imageName  = uniqid('attendance_', true) . '.' . $image->getClientOriginalExtension();
       
            $image->move(public_path('images/attendance_images'), $imageName);
       
            $clockOutImagePath              = 'images/attendance_images/' . $imageName;
            $time                           = Carbon::now('Asia/Kolkata')->format('h:i A');
            $attendance->clock_out          = $time;
            $attendance->clock_out_image    = $clockOutImagePath;
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
        $user       = $request->user();
        $company    = $user->companies()->first();
    
        if (!$company) {
            return response()->json([
                'message' => 'User is not associated with any company.',
            ], 422);
        }
    
        $validator = Validator::make($request->all(), [
            'dates'   => 'required|array|min:1',
            'dates.*' => 'required|date|date_format:Y-m-d',
        ]);
    
        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors'  => $validator->errors(),
            ], 422);
        }
    
        $validated      = $validator->validated();
        $appliedLeaves  = [];
        $skippedDates   = [];
    
        foreach ($validated['dates'] as $date) {
    
            $attendance = Attendance::firstOrNew([
                'user_id'         => $user->id,
                'attendance_date' => $date,
            ]);
    
            if ($attendance->exists) {
                $skippedDates[] = $date;
                continue;
            }
    
            $attendance->company_id       = $company->id;
            $attendance->status           = 'leave';
            $attendance->approval_status  = 'pending';
            $attendance->clock_out        = '-';
            $attendance->clock_out_image  = null;
            $attendance->clock_in         = '-';
            $attendance->clock_in_image   = null;
            $attendance->save();
    
            $appliedLeaves[] = $attendance;
        }
    
        return response()->json([
            'message'       => 'Leave application processed.',
            'applied'       => $appliedLeaves,
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
        $date = $request->query('date');
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

        return response()->json([
            'message'    => 'Attendance approved successfully.',
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

        return response()->json([
            'message'    => 'Attendance rejected successfully.',
            'attendance' => [
                'id'                => $attendance->id,
                'user_id'           => $attendance->user_id,
                'attendance_date'   => $attendance->attendance_date,
                'approval_status'   => $attendance->approval_status,
                'status'            => $attendance->status,
                'clock_in'          => $attendance->clock_in,
                'clock_out'         => $attendance->clock_out,
                'clock_in_image'    => $attendance->clock_in_image,
                'clock_out_image'   => $attendance->clock_out_image,
            ]
        ], 200);
    }
}