<?php

namespace Modules\Udemy\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Modules\Udemy\Models\UdemyCourse;
use Modules\Udemy\Models\UserToken;
use Modules\Udemy\Notifications\Traits\THasTelegramNotification;
use NotificationChannels\Telegram\TelegramMessage;

class StudyCourseCompletedEvent extends Notification
{
    use Queueable;
    use THasTelegramNotification;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public UserToken $userToken,
        public UdemyCourse $udemyCourse
    ) {
        //
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via($notifiable): array
    {
        return ['telegram'];
    }

    public function toTelegram(): TelegramMessage
    {
        return $this->getMessage()
            ->content('Course completed: ' . $this->udemyCourse->title)
            ->escapedLine('');
    }
}
