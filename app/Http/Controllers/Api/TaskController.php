<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Task;
use Illuminate\Http\Request;
use App\Http\Resources\TaskResource;
use Illuminate\Support\Facades\Validator;

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
        $activeCompanyId = request()->attributes->get('activeCompanyId');

        $validator = Validator::make($request->all(), [
            'name'        => 'required|string|max:255',
            'assigned_to' => 'required|exists:users,id',
            'deadline'    => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $data = array_merge($request->all(), [
            'assigned_by' => $authUser->id,
            'company_id'  => $activeCompanyId,
            'status'      => 'pending',
        ]);

        $task = Task::create($data);
        return response()->json($task, 201);
    }


    public function show($id)
    {
        $task = Task::find($id);

        if (!$task) {
            return response()->json(['error' => 'Task not found'], 404);
        }

        return response()->json($task, 200);
    }


    public function update(Request $request, $id)
    {
        $task = Task::find($id);

        if (!$task) {
            return response()->json(['error' => 'Task not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'name'        => 'sometimes|required|string|max:255',
            'assigned_by' => 'sometimes|required|exists:users,id',
            'assigned_to' => 'sometimes|required|exists:users,id',
            'deadline'    => 'nullable|date',
            'status'      => 'sometimes|required|in:pending,in_progress,completed,verified',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $task->update($request->all());
        return response()->json($task, 200);
    }
    

    public function destroy($id)
    {
        $task = Task::find($id);

        if (!$task) {
            return response()->json(['error' => 'Task not found'], 404);
        }

        $task->delete();
        return response()->json(['message' => 'Task deleted successfully'], 200);
    }
}
