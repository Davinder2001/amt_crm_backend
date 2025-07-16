<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Shift;
use App\Models\User;
use App\Models\Attendance;
use Carbon\Carbon;

class DailyAttendanceCheck extends Command
{
    protected $signature = 'daily:attendance-check';
    protected $description = 'Mark employees absent who did not punch or apply for leave during their shift timings';

    public function handle()
    {
        $today  = Carbon::today()->toDateString();
        $now    = Carbon::now('Asia/Kolkata');
        $shifts = Shift::all();

        foreach ($shifts as $shift) {
            $startTime  = Carbon::parse($today . ' ' . $shift->start_time, 'Asia/Kolkata');
            $endTime    = Carbon::parse($today . ' ' . $shift->end_time, 'Asia/Kolkata');

            if ($now->lt($endTime)) {
                $this->info("Skipping shift '{$shift->name}' because it has not ended yet.");
                continue;
            }

            $employees = User::where('shift_id', $shift->id)->get();

            foreach ($employees as $user) {
                $attendance = Attendance::where('user_id', $user->id)
                    ->where('attendance_date', $today)
                    ->first();

                if ($attendance) {
                    if (in_array($attendance->status, ['present', 'leave'])) {
                        $this->info("User {$user->name} already marked as {$attendance->status}.");
                        continue;
                    }
                } else {
                    Attendance::create([
                        'user_id'          => $user->id,
                        'company_id'       => $user->companies()->first()?->id,
                        'attendance_date'  => $today,
                        'status'           => 'absent',
                        'approval_status'  => 'auto',
                        'clock_in'         => '-',
                        'clock_out'        => '-',
                        'clock_in_image'   => null,
                        'clock_out_image'  => null,
                    ]);
                    $this->info("Marked ABSENT: {$user->name} ({$user->id}) for shift '{$shift->name}'.");
                }
            }
        }

        $this->info("✅ Daily attendance check completed.");
        return 0;
    }
}
