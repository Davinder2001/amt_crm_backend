<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use App\Services\SelectedCompanyService;
use App\Http\Resources\TaskResource;

class TaskController extends Controller
{
    public function index()
    {
        $tasks = Task::all();
        return TaskResource::collection($tasks);
    }

    public function store(Request $request)
    {
        $authUser = $request->user();
        $activeCompanyId = SelectedCompanyService::getSelectedCompanyOrFail();

        $validator = Validator::make($request->all(), [
            'name'          => 'required|string|max:255',
            'description'   => 'nullable|string',
            'assigned_to'   => 'required|exists:users,id',
            'assigned_role' => 'nullable|string|max:255',
            'start_date'    => 'nullable|date',
            'end_date'      => 'nullable|date',
            'notify'        => 'nullable|boolean',
            'status'        => 'nullable|in:pending,completed,approved,rejected',
            'attachment'    => 'nullable|file|mimes:jpg,jpeg,png,pdf,docx|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $data = $request->except('attachment');

        if ($request->hasFile('attachment')) {
            $path = $request->file('attachment')->store('task_attachments', 'public');
            $data['attachment_path'] = $path;
        }

        $data['assigned_by'] = $authUser->id;
        $data['company_id'] = $activeCompanyId->company_id;
        $data['notify'] = $request->has('notify') ? (bool) $request->notify : true;
        $data['status'] = $request->input('status', 'pending');

        $task = Task::create($data);

        return response()->json(new TaskResource($task), 201);
    }

    public function show($id)
    {
        $task = Task::find($id);

        if (!$task) {
            return response()->json(['error' => 'Task not found'], 404);
        }

        return response()->json(new TaskResource($task), 200);
    }

    public function update(Request $request, $id)
    {
        $task = Task::find($id);

        if (!$task) {
            return response()->json(['error' => 'Task not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'name'          => 'sometimes|required|string|max:255',
            'description'   => 'nullable|string',
            'assigned_by'   => 'sometimes|required|exists:users,id',
            'assigned_to'   => 'sometimes|required|exists:users,id',
            'assigned_role' => 'nullable|string|max:255',
            'start_date'    => 'nullable|date',
            'end_date'      => 'nullable|date',
            'notify'        => 'nullable|boolean',
            'status'        => 'nullable|in:pending,completed,approved,rejected',
            'attachment'    => 'nullable|file|mimes:jpg,jpeg,png,pdf,docx|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $data = $request->except('attachment');

        if ($request->hasFile('attachment')) {
            if ($task->attachment_path) {
                Storage::disk('public')->delete($task->attachment_path);
            }

            $path = $request->file('attachment')->store('task_attachments', 'public');
            $data['attachment_path'] = $path;
        }

        $task->update($data);

        return response()->json(new TaskResource($task), 200);
    }

    public function destroy($id)
    {
        $task = Task::find($id);

        if (!$task) {
            return response()->json(['error' => 'Task not found'], 404);
        }

        if ($task->attachment_path) {
            Storage::disk('public')->delete($task->attachment_path);
        }

        $task->delete();

        return response()->json(['message' => 'Task deleted successfully'], 200);
    }
}
