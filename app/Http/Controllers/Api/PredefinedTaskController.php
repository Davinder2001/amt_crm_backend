<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PredefinedTask;
use App\Models\User;
use Illuminate\Http\Request;
use App\Services\SelectedCompanyService;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class PredefinedTaskController extends Controller
{
    public function index()
    {
        $allTasks = PredefinedTask::all();
        return $allTasks;
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'                  => 'required|string',
            'description'           => 'nullable|string',
            'assigned_to'           => 'required|exists:users,id',
            'recurrence_type'       => 'required|in:daily,weekly,monthly',
            'recurrence_start_date' => 'required|date',
            'recurrence_end_date'   => 'nullable|date|after_or_equal:recurrence_start_date',
            'notify'                => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $selectedCompany = SelectedCompanyService::getSelectedCompanyOrFail();
        $authUser        = $request->user();
        $data            = $validator->validated();
        $assignedUser    = User::findOrFail($data['assigned_to']);
        $assignedRole    = $assignedUser->getRoleNames()->first();

        $recurrenceDays = null;

        if (!empty($data['recurrence_start_date']) && !empty($data['recurrence_end_date'])) {
            $start  = Carbon::parse($data['recurrence_start_date']);
            $end    = Carbon::parse($data['recurrence_end_date']);
            $period = CarbonPeriod::create($start, $end);

            $recurrenceDays = collect($period)->map(function ($date) {
                return $date->format('l');
            })->unique()->values()->toArray();
        }

        $task = PredefinedTask::create([
            'name'                  => $data['name'],
            'description'           => $data['description'] ?? null,
            'assigned_by'           => $authUser->id,
            'assigned_to'           => $data['assigned_to'],
            'company_id'            => $selectedCompany->company_id,
            'assigned_role'         => $assignedRole,
            'recurrence_type'       => $data['recurrence_type'],
            'recurrence_days'       => $recurrenceDays,
            'recurrence_start_date' => $data['recurrence_start_date'],
            'recurrence_end_date'   => $data['recurrence_end_date'] ?? null,
            'notify'                => $data['notify'] ?? false,
        ]);

        return response()->json([
            'message' => 'Recurring task template created.',
            'task'    => $task,
        ], 201);
    }

    public function show($id)
    {
        $task = PredefinedTask::find($id);

        if (!$task) {
            return response()->json([
                'message' => 'Task not found.'
            ], 404);
        }

        return response()->json([
            'message' => 'Recurring task fetched successfully.',
            'task'    => $task
        ]);
    }



    public function update(Request $request, PredefinedTask $predefinedTask)
    {
        $validator = Validator::make($request->all(), [
            'name'                  => 'string',
            'description'           => 'nullable|string',
            'assigned_by'           => 'exists:users,id',
            'assigned_to'           => 'exists:users,id',
            'company_id'            => 'exists:companies,id',
            'assigned_role'         => 'nullable|string',
            'recurrence_type'       => 'in:daily,weekly,monthly',
            'recurrence_start_date' => 'date',
            'recurrence_end_date'   => 'nullable|date|after_or_equal:recurrence_start_date',
            'notify'                => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();

        if (!empty($data['assigned_to'])) {
            $assignedUser = User::findOrFail($data['assigned_to']);
            $data['assigned_role'] = $assignedUser->getRoleNames()->first();
        }

        if (!empty($data['recurrence_start_date']) && !empty($data['recurrence_end_date'])) {
            $start = Carbon::parse($data['recurrence_start_date']);
            $end   = Carbon::parse($data['recurrence_end_date']);
            $period = CarbonPeriod::create($start, $end);

            $data['recurrence_days'] = collect($period)->map(function ($date) {
                return $date->format('l');
            })->unique()->values()->toArray();
        }

        $predefinedTask->update(array_merge($predefinedTask->toArray(), $data));

        return response()->json([
            'message' => 'Recurring task updated.',
            'task'    => $predefinedTask,
        ]);
    }

    public function destroy(PredefinedTask $predefinedTask)
    {
        $predefinedTask->delete();
        return response()->json(['message' => 'Recurring task deleted.']);
    }
}
