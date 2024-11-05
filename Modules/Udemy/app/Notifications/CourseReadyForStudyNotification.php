<?php

namespace Modules\Udemy\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Modules\Jav\Models\JavMovie;
use Modules\Udemy\Models\UdemyCourse;
use Modules\Udemy\Models\UserToken;
use Modules\Udemy\Notifications\Traits\THasTelegramNotification;
use NotificationChannels\Telegram\TelegramMessage;

class CourseReadyForStudyNotification extends Notification
{
    use Queueable;
    use THasTelegramNotification;

    /**
     * Create a new notification instance.
     */
    public function __construct(public UdemyCourse $course)
    {
        //
    }

    public function via(): array
    {
        return ['telegram'];
    }

    /**
     * @return TelegramMessage
     */
    public function toTelegram(): TelegramMessage
    {
        return $this->getMessage()
            ->content('Course ready for study')
            ->escapedLine('')
            ->line($this->course->title . ' is ready');
    }
}
