<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Notifications\Messages\BroadcastMessage;

class SystemNotification extends Notification implements ShouldBroadcast
{
    use Queueable;

    protected $title;
    protected $message;
    protected $type;
    protected $url;

    public function __construct($title, $message, $type = 'info', $url = null)
    {
        $this->title    = $title;
        $this->message  = $message;
        $this->type     = $type;
        $this->url      = $url;
    }

    public function via($notifiable)
    {
        return ['database', 'broadcast']; 
    }

    public function toDatabase($notifiable)
    {
        return [
            'title'   => $this->title,
            'message' => $this->message,
            'type'    => $this->type,
            'url'     => $this->url,
        ];
    }

    public function toBroadcast($notifiable)
    {
        return new BroadcastMessage([
            'title'      => $this->title,
            'message'    => $this->message,
            'type'       => $this->type,
            'url'        => $this->url,
            'created_at' => now()->toDateTimeString(),
        ]);
    }
}
