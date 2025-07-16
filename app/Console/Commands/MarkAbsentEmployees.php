<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Attendance;
use App\Services\SelectedCompanyService;
use Carbon\Carbon;

class MarkAbsentEmployees extends Command
{
    protected $signature = 'attendance:mark-absent';
    protected $description = 'Mark employees as absent if they did not check in and check out during their shifts';

    public function handle()
    {
        $today = Carbon::today()->toDateString();
        $users = User::whereNotNull('shift_timings')->get();

        foreach ($users as $user) {
            $alreadyMarked = Attendance::where('user_id', $user->id)
                ->where('attendance_date', $today)
                ->exists();

            if ($alreadyMarked) {
                continue;
            }

            $companyId = $user->company_id ?? null;

            Attendance::create([
                'user_id'          => $user->id,
                'company_id'       => $companyId,
                'attendance_date'  => $today,
                'status'           => 'absent',
                'approval_status'  => 'auto',
                'clock_in'         => null,
                'clock_out'        => null,
                'clock_in_image'   => null,
                'clock_out_image'  => null,
            ]);

            $this->info("Marked absent: {$user->name} ({$user->id})");
        }

        $this->info("Attendance marking process completed.");
        return 0;
    }
}