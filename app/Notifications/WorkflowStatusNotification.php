<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class WorkflowStatusNotification extends Notification
{
    use Queueable;
    public function __construct(private readonly string $action, private readonly string $document, private readonly ?string $note = null) {}
    public function via(object $notifiable): array { return ['database']; }
    public function toDatabase(object $notifiable): array { return ['title' => "{$this->document}: {$this->action}", 'action' => $this->action, 'document' => $this->document, 'note' => $this->note]; }
}
