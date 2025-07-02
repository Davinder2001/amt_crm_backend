<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\User;
use App\Models\Package;
use App\Models\TaskReminder;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Notifications\SystemNotification;
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
        $authUser        = $request->user();
        $selectedCompany = SelectedCompanyService::getSelectedCompanyOrFail();
        $company         = $selectedCompany->company;

        // Load package with limits
        $package = Package::with('limits')->find($company->package_id);
        $subscriptionType = $company->subscription_type;

        // Get task limit
        $limit = collect($package->limits)->firstWhere('variant_type', $subscriptionType);
        $dailyTasksLimit = $limit->daily_tasks_number ?? 0;

        // Check if today's task count exceeds limit
        $taskCount = Task::where('company_id', $company->id)
            ->whereDate('created_at', now())
            ->count();

        if ($taskCount >= $dailyTasksLimit) {
            return response()->json([
                'message' => "Daily task limit of {$dailyTasksLimit} reached for this company."
            ], 403);
        }

        // Normalize notify boolean
        $request->merge([
            'notify' => filter_var($request->input('notify'), FILTER_VALIDATE_BOOLEAN)
        ]);

        // Validate input
        $validator = Validator::make($request->all(), [
            'name'          => 'required|string|max:255',
            'description'   => 'required|string',
            'assigned_to'   => 'required|exists:users,id',
            'assigned_role' => 'required|string|max:255',
            'start_date'    => 'required|date',
            'end_date'      => 'required|date',
            'notify'        => 'required|boolean',
            'status'        => 'nullable|in:pending,completed,approved,rejected',
            'attachments'   => 'nullable|array',
            'attachments.*' => 'file|mimes:jpg,jpeg,png,pdf,docx|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $data = $validator->validated();

        $attachmentPaths = [];
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $fileName = uniqid() . '_' . $file->getClientOriginalName();
                $file->move(public_path('task_attachments'), $fileName);
                $publicUrl = url('task_attachments/' . $fileName); 
                $attachmentPaths[] = $publicUrl;
            }
        }


        // Add extra fields
        $data['assigned_by'] = $authUser->id;
        $data['company_id']  = $company->id;
        $data['status']      = $data['status'] ?? 'pending';
        $data['attachments'] = $attachmentPaths;

        // Create task
        $task = Task::create($data);

        // Notify admins
        if ($data['notify']) {
            $this->notifyAdmins(
                'New Task Added',
                "A new task '{$task->name}' has been added.",
                "/tasks/{$task->id}"
            );
        }

        return response()->json(new TaskResource($task), 201);
    }



    /**
     * Display the specified task.
     */
    public function show($id)
    {
        $task = Task::find($id);

        if (!$task) {
            return response()->json([
                'error' => 'Task not found'
            ], 404);
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

            $path                    = $request->file('attachment')->store('task_attachments', 'public');
            $data['attachment_path'] = $path;
        }

        $task->update($data);
        $this->notifyAdmins('Task Updated', "Task '{$task->name}' has been updated.", "/tasks/{$task->id}");

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

        $taskName = $task->name;
        $task->delete();

        $this->notifyAdmins('Task Deleted', "Task '{$taskName}' has been deleted.", "/tasks");

        return response()->json([
            'message' => 'Task deleted successfully'
        ], 200);
    }

    /**
     * Display a listing of tasks assigned to the user with pending status.
     */
    public function assignedPendingTasks()
    {
        $user   = Auth::user();
        $tasks  = Task::where('assigned_to', $user->id)->where('status', 'pending')->get();

        if ($tasks->isEmpty()) {
            return response()->json(
                [
                    'message' => 'No pending tasks found'
                ],
                200
            );
        }

        return TaskResource::collection($tasks);
    }

    /**
     * Display a listing of tasks assigned to the user with working status.
     */
    public function workingTask()
    {
        $user   = Auth::user();
        $tasks  = Task::where('assigned_to', $user->id)->whereIn('status', ['working', 'submitted'])->get();

        if ($tasks->isEmpty()) {
            return response()->json([
                'message' => 'No working or submitted tasks found'
            ], 200);
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
        $this->notifyAdmins('Task Started', "Task '{$task->name}' is now working.", "/tasks/{$task->id}");

        return response()->json(
            [
                'message' => 'Task status updated to working',
                'task' => new TaskResource($task)
            ],
            200
        );
    }

    /**
     * Notify all Admins for current selected company.
     */
    protected function notifyAdmins($title, $message, $url)
    {
        $activeCompanyId    = SelectedCompanyService::getSelectedCompanyOrFail();

        $admins             = User::role('admin')
            ->whereHas('companies', function ($query) use ($activeCompanyId) {
                $query->where('company_user.company_id', $activeCompanyId->company_id);
            })->get();

        foreach ($admins as $admin) {
            $admin->notify(new SystemNotification(
                title: $title,
                message: $message,
                type: 'info',
                url: $url
            ));
        }
    }


    /**
     * End the task
     */
    public function endTask($id)
    {
        $task = Task::find($id);

        if (!$task) {
            return response()->json(['error' => 'Task not found'], 404);
        }

        if (!in_array($task->status, ['working', 'submitted'])) {
            return response()->json([
                'error' => 'Only working or submitted tasks can be ended'
            ], 400);
        }

        $task->status = 'ended';
        $task->save();

        return response()->json([
            'message' => 'Task has been marked as ended.',
            'task'    => $task
        ], 200);
    }



    public function setReminder(Request $request, $taskId)
    {
        $request->validate([
            'reminder_at' => 'required|date|before_or_equal:end_date',
            'end_date'    => 'required|date|after_or_equal:reminder_at',
        ]);

        $reminder = TaskReminder::updateOrCreate(
            [
                'user_id' => Auth::id(),
                'task_id' => $taskId,
            ],
            [
                'reminder_at'   => $request->reminder_at,
                'task_end_date' => $request->end_date,
            ]
        );

        return response()->json([
            'message'   => 'Reminder set successfully.',
            'reminder'  => $reminder,
        ]);
    }


    public function viewReminder($taskId)
    {
        $reminder = TaskReminder::where('task_id', $taskId)->where('user_id', Auth::id())->first();

        if (!$reminder) {
            return response()->json(['message' => 'No reminder found.'], 404);
        }

        return response()->json([
            'message'   => 'Reminder retrieved.',
            'reminder'  => $reminder,
        ]);
    }

    public function updateReminder(Request $request, $taskId)
    {
        $request->validate([
            'reminder_at' => 'required|date|before_or_equal:end_date',
            'end_date'    => 'required|date|after_or_equal:reminder_at',
        ]);

        $reminder = TaskReminder::where('task_id', $taskId)->where('user_id', Auth::id())->first();

        if (!$reminder) {
            return response()->json(['message' => 'Reminder not found.'], 404);
        }

        $reminder->update([
            'reminder_at'   => $request->reminder_at,
            'task_end_date' => $request->end_date,
        ]);

        return response()->json([
            'message' => 'Reminder updated successfully.',
            'reminder' => $reminder,
        ]);
    }
}
