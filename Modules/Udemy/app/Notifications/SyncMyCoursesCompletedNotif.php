<?php

namespace Modules\Udemy\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Modules\Udemy\Models\UserToken;
use Modules\Udemy\Notifications\Traits\THasTelegram;
use NotificationChannels\Telegram\TelegramMessage;

class SyncMyCoursesCompletedNotif extends Notification implements ShouldQueue
{
    use Queueable;
    use THasTelegram;

    public function __construct(
        public UserToken $userToken
    ) {
    }

    public function toTelegram(UserToken $notifiable): TelegramMessage
    {
        $completedCourses = $notifiable->courses()
            ->wherePivot('completion_ratio', 100)->count();
        $notCompletedCourses = $notifiable->courses()
            ->wherePivot('completion_ratio', '<', 100)->count();

        return $this->getMessage()
            ->content('*Courses sync completed*')
            ->escapedLine('')
            ->line('*User*: ' . $notifiable->id)
            ->line('*Courses*: ' . $notifiable->courses()->count())
            ->line('*Completed courses*: ' . $completedCourses)
            ->line('*Not completed courses*: ' . $notCompletedCourses);
    }
}
