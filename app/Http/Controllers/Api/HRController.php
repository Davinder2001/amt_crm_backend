<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Shift;
use App\Models\User;
use Carbon\Carbon;

class HRController extends Controller
{
    public function dashboardSummary()
    {
        $today        = Carbon::today()->toDateString();
        $yesterday    = Carbon::yesterday()->toDateString();
        $monthStart   = Carbon::now()->startOfMonth()->toDateString();
        $monthEnd     = Carbon::now()->endOfMonth()->toDateString();
        $userType     = 'employee';

        // Total employees (monthly comparison)
        $totalEmployees        = User::where('user_type', $userType)->count();
        $totalEmployeesLastDay = User::where('user_type', $userType)
            ->whereDate('created_at', '<=', $yesterday)->count();
        $newEmployeesThisMonth = $totalEmployees - $totalEmployeesLastDay;

        // Attendance counts
        $presentToday      = Attendance::whereDate('attendance_date', $today)->where('status', 'present')->count();
        $presentYesterday  = Attendance::whereDate('attendance_date', $yesterday)->where('status', 'present')->count();

        $absentToday       = $totalEmployees - $presentToday;
        $absentYesterday   = $totalEmployeesLastDay - $presentYesterday;

        // Leaves
        $monthlyLeaves = Attendance::whereBetween('attendance_date', [$monthStart, $monthEnd])
            ->where('status', 'leave')
            ->with('employee:id,name')
            ->get();

        $todayLeaves = Attendance::whereDate('attendance_date', $today)->where('status', 'leave')->count();
        $yesterdayLeaves = Attendance::whereDate('attendance_date', $yesterday)->where('status', 'leave')->count();

        // Shifts for time comparison
        $shifts = Shift::pluck('start_time')->toArray();

        // Early Departures
        $earlyDepartures = Attendance::whereDate('attendance_date', $today)
            ->whereNotNull('clock_out')
            ->where(function ($query) use ($shifts) {
                foreach ($shifts as $startTime) {
                    $query->orWhereTime('clock_out', '<', $startTime);
                }
            })
            ->with('employee:id,name')
            ->get();

        $earlyDeparturesCountToday = $earlyDepartures->count();
        $earlyDeparturesCountYesterday = Attendance::whereDate('attendance_date', $yesterday)
            ->whereNotNull('clock_out')
            ->where(function ($query) use ($shifts) {
                foreach ($shifts as $startTime) {
                    $query->orWhereTime('clock_out', '<', $startTime);
                }
            })
            ->count();

        // Late Arrivals
        $lateArrival = Attendance::whereDate('attendance_date', $today)
            ->whereNotNull('clock_in')
            ->where(function ($query) use ($shifts) {
                foreach ($shifts as $startTime) {
                    $query->orWhereTime('clock_in', '>', $startTime);
                }
            })
            ->with('employee:id,name')
            ->get();

        $lateArrivalCountToday = $lateArrival->count();
        $lateArrivalCountYesterday = Attendance::whereDate('attendance_date', $yesterday)
            ->whereNotNull('clock_in')
            ->where(function ($query) use ($shifts) {
                foreach ($shifts as $startTime) {
                    $query->orWhereTime('clock_in', '>', $startTime);
                }
            })
            ->count();

        // Summary message
        $message = "Today HR Summary: ";
        $message .= "Total Employees: $totalEmployees (+$newEmployeesThisMonth new this month). ";
        $message .= "Present Today: $presentToday (" . $this->compareDiffText($presentToday, $presentYesterday) . "). ";
        $message .= "Absent Today: $absentToday (" . $this->compareDiffText($absentToday, $absentYesterday) . "). ";
        $message .= "Time Offs (Leave): $todayLeaves (" . $this->compareDiffText($todayLeaves, $yesterdayLeaves) . "). ";
        $message .= "Late Arrivals: $lateArrivalCountToday (" . $this->compareDiffText($lateArrivalCountToday, $lateArrivalCountYesterday) . "). ";
        $message .= "Early Departures: $earlyDeparturesCountToday (" . $this->compareDiffText($earlyDeparturesCountToday, $earlyDeparturesCountYesterday) . ").";

        return response()->json([
            'summary' => [
                'total_employees'     => $totalEmployees,
                'present_today'       => $presentToday,
                'absent_today'        => $absentToday,
                'late_arrival'        => $lateArrivalCountToday,
                'early_departures'    => $earlyDeparturesCountToday,
                'time_offs'           => $todayLeaves,
                'message'             => $message,
            ],
            'early_departures_list' => $earlyDepartures,
            'late_arrival_list'     => $lateArrival,
            'monthly_leaves'        => $monthlyLeaves,
        ]);
    }

    private function compareDiffText($todayValue, $yesterdayValue)
    {
        $diff = $todayValue - $yesterdayValue;

        if ($diff === 0) return 'no change from yesterday';
        if ($diff > 0) return "$diff more than yesterday";
        return abs($diff) . ' less than yesterday';
    }
}
