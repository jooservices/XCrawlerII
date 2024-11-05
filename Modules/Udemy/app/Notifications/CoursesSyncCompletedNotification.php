<?php

namespace Modules\Udemy\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Modules\Udemy\Models\UserToken;
use Modules\Udemy\Notifications\Traits\THasTelegramNotification;
use Modules\Udemy\Services\Client\Entities\CoursesEntity;
use NotificationChannels\Telegram\TelegramMessage;

class CoursesSyncCompletedNotification extends Notification
{
    use Queueable;
    use THasTelegramNotification;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public UserToken $userToken,
        public CoursesEntity $coursesEntity
    ) {
        //
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(): array
    {
        return ['telegram'];
    }

    public function toTelegram(): TelegramMessage
    {
        return $this->getMessage()
            ->content('Courses sync completed')
            ->escapedLine('')
            ->line('User ' . $this->userToken->token)
            ->line('Courses: ' . $this->coursesEntity->getCount());
        /**
         * Get not completed courses count
         */
    }
}
