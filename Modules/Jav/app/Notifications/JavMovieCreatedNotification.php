<?php

namespace Modules\Jav\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Modules\Jav\Models\JavMovie;
use NotificationChannels\Telegram\TelegramMessage;

class JavMovieCreatedNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via($notifiable): array
    {
        return ['telegram'];
    }

    public function toTelegram(JavMovie $notifiable)
    {
        return TelegramMessage::create()
            // Optional recipient user id.
            ->to('-4556029367')

            // Markdown supported.
            ->content('New JAV movie created')
            ->line('')
            ->line($notifiable->dvd_id)
            ->line('Size: ' . $notifiable->size);
        //            ->lineIf($notifiable->amount > 0, "Amount paid: {$notifiable->amount}")
        //            ->line("Thank you!")

        // (Optional) Blade template for the content.
        // ->view('notification', ['url' => $url])

        // (Optional) Inline Buttons
        //            ->button('View Invoice', $url)
        //            ->button('Download Invoice', $url);

        // (Optional) Conditional notification.
        // Only send if amount is greater than 0. Otherwise, don't send.
        // ->sendWhen($notifiable->amount > 0)

        // (Optional) Inline Button with Web App
        // ->buttonWithWebApp('Open Web App', $url)

        // (Optional) Inline Button with callback. You can handle callback in your bot instance
        // ->buttonWithCallback('Confirm', 'confirm_invoice ' . $this->invoice->id)
    }
}
