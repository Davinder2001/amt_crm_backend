<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\TaskHistory;
use App\Http\Resources\TaskHistoryResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class TaskHistoryController extends Controller
{

    /**
     * Display a listing of the task histories.
     */
    public function index()
    {
        $histories = TaskHistory::get();
        return response()->json($histories);
    }


    /**
     * Submit a new task history entry and mark task as submitted.
     */
    public function store(Request $request, $taskId)
    {
        $validator = Validator::make($request->all(), [
            'description'      => 'required|string',
            'attachments.*'    => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
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

        // Allow submissions until task is 'approved' or 'completed'
        if (in_array($task->status, ['completed', 'submitted'], true)) {
            return response()->json([
                'message' => "Task is already {$task->status}."
            ], 422);
        }

        // Update task status to 'submitted' on every submission
        $task->update(['status' => 'in_progress']);

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

        $history->update([
            'status'       => 'approved',
            'admin_remark' => 'Approved by admin'
        ]);
        
        $task = $history->task;
        $task->update(['status' => 'completed']);

        return response()->json([
            'message' => 'Task approved.'
        ]);
    }

    /**
     * Reject a submitted task history (admin only).
     */
    public function reject(Request $request, $id)
    {
        $history = TaskHistory::findOrFail($id);
        $remark = $request->input('remark', 'Rejected by admin');

        $history->update([
            'status'       => 'rejected',
            'admin_remark' => $remark
        ]);

        $task = $history->task;
        $task->update(['status' => 'rejected']);

        return response()->json(['message' => 'Task rejected.']);
    }

    /**
     * List history entries for a single task.
     */
    

    public function historyByTask($id)
    {
        $userId = Auth::id();

        $histories = TaskHistory::where('task_id', $id)
            ->where('submitted_by', $userId)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'status'    => 'success',
            'task_id'   => $id,
            'user_id'   => $userId,
            'histories' => TaskHistoryResource::collection($histories),
        ]);
    }



    /**
     * List all history entries (admins see all; others see only theirs).
     */
    public function allHistory()
    {
        $user   = Auth::user();
        $query  = TaskHistory::with(['submitter', 'task']);

        if ($user->role !== 'admin') {
            $query->whereHas('task', function ($q) use ($user) {
                $q->where('assigned_to', $user->id)
                    ->orWhere('assigned_by', $user->id);
            });
        }

        $histories = $query->latest()->get();
        return response()->json($histories);
    }

    public function acceptTask($id)
    {
        $task = Task::findOrFail($id);

        if ($task->assigned_to !== Auth::id()) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        if (in_array($task->status, ['working', 'submitted', 'completed', 'approved'], true)) {
            return response()->json([
                'message' => "Cannot accept a task when its status is '{$task->status}'."
            ], 422);
        }

        $task->update(['status' => 'working']);

        $history = TaskHistory::create([
            'task_id'      => $task->id,
            'submitted_by' => Auth::id(),
            'description'  => 'Task accepted by employee',
            'attachments'  => [],
            'status'       => 'working',
        ]);

        return response()->json([
            'message' => 'Task accepted; status updated to working.',
            'history' => $history,
        ], 200);
    }
}
