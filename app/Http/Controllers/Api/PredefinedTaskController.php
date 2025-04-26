<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PredefinedTask;
use Illuminate\Http\Request;

class PredefinedTaskController extends Controller
{
    public function index()
    {
        return PredefinedTask::all();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'description' => 'nullable|string',
            'assigned_by' => 'required|exists:users,id',
            'assigned_to' => 'required|exists:users,id',
            'company_id' => 'required|exists:companies,id',
            'assigned_role' => 'nullable|string',
            'recurrence_type' => 'required|in:daily,weekly,monthly',
            'recurrence_days' => 'nullable|array',
            'recurrence_start_date' => 'required|date',
            'recurrence_end_date' => 'nullable|date|after_or_equal:recurrence_start_date',
            'notify' => 'boolean',
        ]);

        $task = PredefinedTask::create($validated);

        return response()->json([
            'message' => 'Recurring task template created.',
            'task' => $task,
        ], 201);
    }

    public function show(PredefinedTask $predefinedTask)
    {
        return $predefinedTask;
    }

    public function update(Request $request, PredefinedTask $predefinedTask)
    {
        $validated = $request->validate([
            'name' => 'string',
            'description' => 'nullable|string',
            'assigned_by' => 'exists:users,id',
            'assigned_to' => 'exists:users,id',
            'company_id' => 'exists:companies,id',
            'assigned_role' => 'nullable|string',
            'recurrence_type' => 'in:daily,weekly,monthly',
            'recurrence_days' => 'nullable|array',
            'recurrence_start_date' => 'date',
            'recurrence_end_date' => 'nullable|date|after_or_equal:recurrence_start_date',
            'notify' => 'boolean',
        ]);

        $predefinedTask->update($validated);

        return response()->json([
            'message' => 'Recurring task updated.',
            'task' => $predefinedTask,
        ]);
    }

    public function destroy(PredefinedTask $predefinedTask)
    {
        $predefinedTask->delete();
        return response()->json(['message' => 'Recurring task deleted.']);
    }
}
