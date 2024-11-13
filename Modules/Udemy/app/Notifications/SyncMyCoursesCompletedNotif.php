<?php

namespace Modules\Udemy\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Modules\Udemy\Models\UserToken;
use NotificationChannels\Telegram\TelegramMessage;

class SyncMyCoursesCompletedNotif extends AbstractToTelegramNotification
{
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
