<?php

namespace Modules\Udemy\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Modules\Jav\Models\JavMovie;
use Modules\Udemy\Models\UdemyCourse;
use Modules\Udemy\Models\UserToken;
use NotificationChannels\Telegram\TelegramMessage;

class CourseReadyForStudyNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public UdemyCourse $course)
    {
        //
    }

    public function via($notifiable): array
    {
        return ['telegram'];
    }

    /**
     * @param UserToken $notifiable
     * @return TelegramMessage
     */
    public function toTelegram(UserToken $notifiable): TelegramMessage
    {
        return TelegramMessage::create()
            // Optional recipient user id.
            ->to(config('udemy.notifications.telegram.chat_id'))

            // Markdown supported.
            ->content('Course ready for study')
            ->escapedLine('')
            ->line($this->course->title . ' is ready');

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
