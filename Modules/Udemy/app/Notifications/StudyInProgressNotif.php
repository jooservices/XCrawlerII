<?php

namespace Modules\Udemy\Notifications;

use Illuminate\Bus\Batch;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Modules\Udemy\Models\UdemyCourse;
use Modules\Udemy\Models\UserToken;
use Modules\Udemy\Notifications\Traits\THasTelegram;
use NotificationChannels\Telegram\TelegramMessage;

class StudyInProgressNotif extends Notification implements ShouldQueue
{
    use Queueable;
    use THasTelegram;

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
