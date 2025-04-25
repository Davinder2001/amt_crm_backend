<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use App\Services\SelectedCompanyService;
use App\Http\Resources\TaskResource;
use Illuminate\Support\Facades\Auth;


class TaskController extends Controller
{
    /**
     * Display a listing of the tasks.
     */
    public function index()
    {
        $tasks = Task::all();
        return TaskResource::collection($tasks);
    }
    
    /**
     * Store a newly created task in storage.
     */
    public function store(Request $request)
    {
        $authUser = $request->user();
        $activeCompanyId = SelectedCompanyService::getSelectedCompanyOrFail();

        $validator = Validator::make($request->all(), [
            'name'          => 'required|string|max:255',
            'description'   => 'required|string',
            'assigned_to'   => 'required|exists:users,id',
            'assigned_role' => 'required|string|max:255',
            'start_date'    => 'required|date',
            'end_date'      => 'required|date',
            'notify'        => 'required|boolean',
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

    /**
     * Display the specified task.
     */
    public function show($id)
    {
        $task = Task::find($id);

        if (!$task) {
            return response()->json(['error' => 'Task not found'], 404);
        }

        return response()->json(new TaskResource($task), 200);
    }

    /**
     * Update the specified task in storage.
     */
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


    /**
     * Remove the specified task from storage.
     */
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


    /**
     * Display a listing of tasks assigned to the user with pending status.
     */
    public function assignedPendingTasks()
    {
       
        $user = Auth::user();
        $tasks = Task::where('assigned_to', $user->id)->where('status', 'pending')->get();
        
        if ($tasks->isEmpty()) {
            return response()->json(['error' => 'No working tasks found'], 404);
        }

        return TaskResource::collection($tasks);
    }

    /**
     * Display a listing of tasks assigned to the user with working status.
     */
    public function workingTask()
    {
        $user = Auth::user();
        $tasks = Task::where('assigned_to', $user->id)->where('status', 'working')->get();

        if ($tasks->isEmpty()) {
            return response()->json(['error' => 'No working tasks found'], 404);
        }

        return TaskResource::collection($tasks);
    }

    /**
     * Change task status from pending to working.
     */
    public function markAsWorking($id)
    {
        $task = Task::find($id);

        if (!$task) {
            return response()->json(['error' => 'Task not found'], 404);
        }

        if ($task->status !== 'pending') {
            return response()->json(['error' => 'Task is not in pending status'], 400);
        }

        $task->status = 'working';
        $task->save();

        return response()->json(['message' => 'Task status updated to working', 'task' => $task], 200);
    }


}
