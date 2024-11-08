<?php

namespace Modules\Udemy\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Modules\Udemy\Models\UserToken;
use Modules\Udemy\Notifications\Traits\THasTelegramNotification;
use NotificationChannels\Telegram\TelegramMessage;

class CoursesSyncCompletedNotification extends Notification
{
    use Queueable;
    use THasTelegramNotification;

    /**
     * Create a new notification instance.
     */
    public function __construct(public UserToken $userToken)
    {
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
        $notCompletedCourses = $this->userToken->courses()
            ->wherePivot('completion_ratio', '<', 100)->count();

        return $this->getMessage()
            ->content('Courses sync completed')
            ->escapedLine('')
            ->line('User ' . $this->userToken->token)
            ->line('Courses: ' . $this->userToken->courses()->count())
            ->line('Not completed courses: ' . $notCompletedCourses);
    }
}
