<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

/**
 * Notifikasi in-app (database) keterlambatan pelaporan SUSENAS-SERUTI.
 * Dibuat oleh command terjadwal MonitorReportingDeadlines.
 */
class ReportingDeadlineNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $level,
        public string $title,
        public string $message,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'level' => $this->level,
            'title' => $this->title,
            'message' => $this->message,
        ];
    }
}
