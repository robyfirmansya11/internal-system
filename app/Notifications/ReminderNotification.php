<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

class ReminderNotification extends Notification
{
    public function __construct(private readonly string $title, private readonly string $message, private readonly string $key) {}
    public function via(object $notifiable): array { return ['database']; }
    public function toDatabase(object $notifiable): array { return ['title' => $this->title, 'message' => $this->message, 'reminder_key' => $this->key]; }
}
