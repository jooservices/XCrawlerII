<?php

namespace Modules\Udemy\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Modules\Udemy\Models\UdemyCourse;
use Modules\Udemy\Models\UserToken;
use Modules\Udemy\Notifications\Traits\THasTelegram;
use NotificationChannels\Telegram\TelegramMessage;

class CourseReadyForStudyNotif extends Notification implements ShouldQueue
{
    use Queueable;
    use THasTelegram;

    public function __construct(public UdemyCourse $udemyCourse)
    {
    }

    public function toTelegram(UserToken $notifiable): TelegramMessage
    {
        return $this->getMessage()
            ->content('*' . $this->udemyCourse->title . '*')
            ->escapedLine('')
            ->line('Ready for study')
            ->line('*Items*: ' . $this->udemyCourse->items()->count());
    }
}
