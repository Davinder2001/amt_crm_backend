<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\TaskHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class TaskHistoryController extends Controller
{
    public function store(Request $request, $taskId)
    {
        $validator = Validator::make($request->all(), [
            'description' => 'nullable|string',
            'attachments.*' => 'nullable|image|mimes:jpg,jpeg,png|max:5120',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $task = Task::findOrFail($taskId);

        // dd($task);

        $images = [];
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $filename = uniqid('task_attach_') . '.' . $file->getClientOriginalExtension();
                $file->move(public_path('uploads/tasks'), $filename);
                $images[] = asset('uploads/tasks/' . $filename);
            }
        }

        $history = TaskHistory::create([
            'task_id' => $task->id,
            'submitted_by' => Auth::id(),
            'description' => $request->description,
            'attachments' => $images,
            'status' => 'submitted',
        ]);

        return response()->json(['message' => 'Task update submitted.', 'history' => $history], 201);
    }

    public function approve($id)
    {
        $history = TaskHistory::findOrFail($id);
        $history->status = 'approved';
        $history->admin_remark = 'Approved by admin';
        $history->save();

        $history->task->update(['status' => 'approved']);

        return response()->json(['message' => 'Task approved.']);
    }

    public function reject(Request $request, $id)
    {
        $history = TaskHistory::findOrFail($id);
        $history->status = 'rejected';
        $history->admin_remark = $request->remark ?? 'Rejected by admin';
        $history->save();

        $history->task->update(['status' => 'rejected']);

        return response()->json(['message' => 'Task rejected.']);
    }

    public function historyByTask($taskId)
    {
        $histories = TaskHistory::where('task_id', $taskId)->with('submitter')->latest()->get();
        return response()->json($histories);
    }

    
    public function allHistory()
    {
        $histories = TaskHistory::get();
        return response()->json($histories);
    }
}
