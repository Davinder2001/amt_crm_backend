<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\Shift;
use App\Models\User;
use Carbon\Carbon;

class HRController extends Controller
{
    public function dashboardSummary()
    {
        $today          = Carbon::today()->toDateString();
        $monthStart     = Carbon::now()->startOfMonth()->toDateString();
        $monthEnd       = Carbon::now()->endOfMonth()->toDateString();
        $userType       = 'employee';



        $totalEmployees = User::where('user_type', $userType)
                        ->with(['roles.permissions', 'companies', 'employeeDetail'])
                        ->count();

        $presentToday   = Attendance::whereDate('attendance_date', $today)
                        ->where('status', 'present')
                        ->get();

        $absentToday    = $totalEmployees - $presentToday;
        $shifts         = Shift::pluck('start_time');


        $earlyDepartures = Attendance::whereDate('attendance_date', $today)
            ->whereNotNull('clock_out')
            ->whereTime('clock_out', '<', $shifts)
            ->with('employee:id,name')
            ->get();

        $lateArrival = Attendance::whereDate('attendance_date', $today)
            ->whereNotNull('clock_in')
            ->whereTime('clock_in', '<', $shifts)
            ->with('employee:id,name')
            ->get();
            
        $monthlyLeaves = Attendance::whereBetween('attendance_date', [$monthStart, $monthEnd])
            ->where('status', 'leave')
            ->with('employee:id,name')
            ->get();

        return response()->json([
            'summary' => [
                'total_employees' => $totalEmployees,
                'present_today' => $presentToday,
                'absent_today' => $absentToday,
            ],
            'early_departures' => $earlyDepartures,
            'monthly_leaves' => $monthlyLeaves,
            'lateArrival' => $lateArrival,
        ]);
    }
}
