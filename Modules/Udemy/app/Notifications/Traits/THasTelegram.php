<?php

namespace Modules\Udemy\Notifications\Traits;

use Modules\Udemy\Models\UserToken;
use NotificationChannels\Telegram\TelegramMessage;

trait THasTelegram
{
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
