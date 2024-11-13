<?php

namespace Modules\Udemy\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Modules\Udemy\Models\UserToken;
use NotificationChannels\Telegram\TelegramMessage;

abstract class AbstractToTelegramNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Get the notification's delivery channels.
     */
    public function via(): array
    {
        return ['telegram'];
    }

    protected function getMessage(): TelegramMessage
    {
        return TelegramMessage::create()
            ->to(config('udemy.notifications.telegram.chat_id'));
    }

    abstract public function toTelegram(UserToken $notifiable): TelegramMessage;
}
