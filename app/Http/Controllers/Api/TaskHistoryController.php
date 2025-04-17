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
    /**
     * Submit a new task history entry and mark task as submitted.
     */
    public function store(Request $request, $taskId)
    {
        $validator = Validator::make($request->all(), [
            'description'      => 'required|string',
            'attachments.*'    => 'nullable|image|mimes:jpg,jpeg,png|max:5120',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $task = Task::findOrFail($taskId);

        if ($task->assigned_to != Auth::id()) {
            return response()->json([
                'message' => 'You are not authorized to update this task.'
            ], 403);
        }

        if (in_array($task->status, ['completed', 'approved', 'submitted'], true)) {
            return response()->json([
                'message' => "Task is already {$task->status}."
            ], 422);
        }

        // update main task status to 'submitted'
        $task->update(['status' => 'submitted']);

        $images = [];
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $filename = uniqid('task_attach_') . '.' . $file->getClientOriginalExtension();
                $file->move(public_path('uploads/tasks'), $filename);
                $images[] = asset('uploads/tasks/' . $filename);
            }
        }

        $history = TaskHistory::create([
            'task_id'      => $task->id,
            'submitted_by' => Auth::id(),
            'description'  => $request->description,
            'attachments'  => $images,
            'status'       => 'submitted',
        ]);

        return response()->json([
            'message' => 'Task update has been successfully submitted.',
            'history' => $history
        ], 201);
    }

    /**
     * Approve a submitted task history (admin only).
     */
    public function approve($id)
    {
        $history = TaskHistory::findOrFail($id);

        // update history entry
        $history->update([
            'status'       => 'approved',
            'admin_remark' => 'Approved by admin'
        ]);

        // update the related task status
        $task = $history->task;
        $task->update(['status' => 'approved']);

        return response()->json(['message' => 'Task approved.']);
    }

    /**
     * Reject a submitted task history (admin only).
     */
    public function reject(Request $request, $id)
    {
        $history = TaskHistory::findOrFail($id);

        $remark = $request->input('remark', 'Rejected by admin');
        // update history entry
        $history->update([
            'status'       => 'rejected',
            'admin_remark' => $remark
        ]);

        // update the related task status
        $task = $history->task;
        $task->update(['status' => 'rejected']);

        return response()->json(['message' => 'Task rejected.']);
    }

    /**
     * List history entries for a single task.
     */
    public function historyByTask($taskId)
    {
        $task = Task::findOrFail($taskId);
        $user = Auth::user();

        if ($user->role !== 'admin' && $task->assigned_to !== $user->id && $task->assigned_by !== $user->id) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $histories = TaskHistory::where('task_id', $taskId)
                                 ->with('submitter')
                                 ->latest()
                                 ->get();

        return response()->json($histories);
    }

    /**
     * List all history entries (admins see all; others see only theirs).
     */
    public function allHistory()
    {
        $user = Auth::user();
        $query = TaskHistory::with(['submitter', 'task']);

        if ($user->role !== 'admin') {
            $query->whereHas('task', function ($q) use ($user) {
                $q->where('assigned_to', $user->id)
                  ->orWhere('assigned_by', $user->id);
            });
        }

        $histories = $query->latest()->get();
        return response()->json($histories);
    }
}
