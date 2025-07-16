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
        return PredefinedTask::all();
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
            return response()->json(['message' => 'Validation failed.', 'errors' => $validator->errors()], 422);
        }

        $data           = $validator->validated();
        $authUser       = $request->user();
        $company        = SelectedCompanyService::getSelectedCompanyOrFail();
        $assignedUser   = User::findOrFail($data['assigned_to']);

        $data['assigned_by']    = $authUser->id;
        $data['company_id']     = $company->company_id;
        $data['assigned_role']  = $assignedUser->getRoleNames()->first();
        $data['recurrence_days'] = $this->calculateRecurrenceDays($data['recurrence_start_date'], $data['recurrence_end_date']);
        $data['notify']         = $data['notify'] ?? false;

        $task = PredefinedTask::create($data);

        return response()->json(['message' => 'Recurring task template created.', 'task' => $task], 201);
    }

    public function show($id)
    {
        $task = PredefinedTask::find($id);

        if (!$task) {
            return response()->json(['message' => 'Task not found.'], 404);
        }

        return response()->json(['message' => 'Recurring task fetched successfully.', 'task' => $task]);
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
            return response()->json(['message' => 'Validation failed.', 'errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();

        if (!empty($data['assigned_to'])) {
            $assignedUser = User::findOrFail($data['assigned_to']);
            $data['assigned_role'] = $assignedUser->getRoleNames()->first();
        }

        if (!empty($data['recurrence_start_date']) && !empty($data['recurrence_end_date'])) {
            $data['recurrence_days'] = $this->calculateRecurrenceDays($data['recurrence_start_date'], $data['recurrence_end_date']);
        }

        $predefinedTask->update($data);

        return response()->json(['message' => 'Recurring task updated.', 'task' => $predefinedTask]);
    }

    public function destroy(PredefinedTask $predefinedTask)
    {
        $predefinedTask->delete();
        return response()->json(['message' => 'Recurring task deleted.']);
    }

    /**
     * Helper to calculate unique days between two dates.
     */
    private function calculateRecurrenceDays($startDate, $endDate)
    {
        if (!$startDate || !$endDate) return null;

        $period = CarbonPeriod::create(Carbon::parse($startDate), Carbon::parse($endDate));

        return collect($period)->map(fn ($date) => $date->format('l'))->unique()->values()->toArray();
    }
}