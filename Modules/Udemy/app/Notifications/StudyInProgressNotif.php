<?php

namespace Modules\Udemy\Notifications;

use Illuminate\Bus\Batch;
use Modules\Udemy\Models\UdemyCourse;
use Modules\Udemy\Models\UserToken;
use NotificationChannels\Telegram\TelegramMessage;

class StudyInProgressNotif extends AbstractToTelegramNotification
{
    public function __construct(public UdemyCourse $udemyCourse, public Batch $batch)
    {
    }

    public function toTelegram(UserToken $notifiable): TelegramMessage
    {
        return $this->getMessage()
            ->content('[' . $this->udemyCourse->title . '](' . $this->udemyCourse->getLink() . ')')
            ->escapedLine('')
            ->line('*Completed*: ' . $this->batch->progress() . '%')
            ->line('*Total jobs*: ' . $this->batch->totalJobs)
            ->line('*Finished jobs*:' . $this->batch->processedJobs());
    }
}
