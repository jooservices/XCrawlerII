<?php

namespace Modules\Udemy\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Modules\Udemy\Models\CurriculumItem;
use Modules\Udemy\Models\UdemyCourse;
use Modules\Udemy\Models\UserToken;
use Modules\Udemy\Notifications\Traits\THasTelegram;
use NotificationChannels\Telegram\TelegramMessage;

class CourseLectureCompletedNotif extends Notification implements ShouldQueue
{
    use Queueable;
    use THasTelegram;

    public function __construct(
        public UdemyCourse $udemyCourse,
        public CurriculumItem $curriculumItem
    ) {
        //
    }

    public function toTelegram(UserToken $notifiable): TelegramMessage
    {
        return $this->getMessage()
            ->content('*' . $this->udemyCourse->title . '*')
            ->escapedLine('')
            ->line('*Lecture*: ' . $this->curriculumItem->title);
    }
}
