<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class CaseNotification extends Notification
{
    use Queueable;

    private $details;

    public function __construct($details)
    {
        $this->details = $details;
    }

    public function via($notifiable)
    {
        return ['database']; // سنخزن الإشعار في قاعدة البيانات
    }

    public function toArray($notifiable)
    {
        return [
            'title' => $this->details['title'],
            'message' => $this->details['message'],
            'case_id' => $this->details['case_id'],
            'type' => $this->details['type'], // مثلاً: offer_received, case_accepted
        ];
    }
}