<?php

namespace Modules\Udemy\Notifications;

use Illuminate\Bus\Batch;
use Modules\Udemy\Models\UdemyCourse;
use Modules\Udemy\Models\UserToken;
use NotificationChannels\Telegram\TelegramMessage;

class CourseReadyForStudyNotif extends AbstractToTelegramNotification
{
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
