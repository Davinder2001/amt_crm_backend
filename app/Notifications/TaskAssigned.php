<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class TaskAssigned extends Notification
{
    use Queueable;

    protected $task;
    protected $assignedBy;

    
    /**
     * Create a new notification instance.
     */
    public function __construct($task, $assignedBy)
    {
        $this->task = $task;
        $this->assignedBy = $assignedBy;
    }


    /**
     * Get the notification's delivery channels.
     */
    public function via($notifiable): array
    {
        return ['database'];
    }


    /**
     * Get the array representation of the notification.
     */
    public function toArray($notifiable): array
    {
        return [
            'title' => 'Task Assigned',
            'message' => "A new task '{$this->task->title}' has been assigned to you.",
            'task_id' => $this->task->id,
            'assigned_by' => $this->assignedBy->name,
        ];
    }
}
