<?php

namespace Modules\Udemy\Notifications;

use Modules\Udemy\Models\CurriculumItem;
use Modules\Udemy\Models\UdemyCourse;
use Modules\Udemy\Models\UserToken;
use NotificationChannels\Telegram\TelegramMessage;

class CourseLectureCompletedNotif extends AbstractToTelegramNotification
{
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
