<?php

namespace Modules\Udemy\Notifications\Traits;

use NotificationChannels\Telegram\TelegramMessage;

trait THasTelegramNotification
{
    protected function getMessage(): TelegramMessage
    {
        return TelegramMessage::create()
            ->to(config('udemy.notifications.telegram.chat_id'));
    }
}
