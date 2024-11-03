<?php

namespace Modules\Udemy\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Modules\Udemy\Models\UserToken;
use Modules\Udemy\Services\Client\Entities\CoursesEntity;
use NotificationChannels\Telegram\TelegramMessage;

class CoursesSyncCompletedNotification extends Notification
{
    use Queueable;

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
    public function via($notifiable): array
    {
        return ['telegram'];
    }

    public function toTelegram(UserToken $notifiable)
    {
        return TelegramMessage::create()
            // Optional recipient user id.
            ->to(config('udemy.notifications.telegram.chat_id'))

            // Markdown supported.
            ->content('Courses sync completed')
            ->escapedLine('')
            ->line('User ' . $this->userToken->token)
            ->line('Courses: ' . $this->coursesEntity->getCount());
        /**
         * Get not completed courses count
         */
    }
}
