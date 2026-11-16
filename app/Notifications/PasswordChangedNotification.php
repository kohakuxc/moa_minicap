<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PasswordChangedNotification extends Notification
{
    use Queueable;

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your MOA password was changed')
            ->greeting('Your password has been changed.')
            ->line('This is a confirmation that your MOA account password was updated successfully.')
            ->line('If you did not make this change, contact the MOA clinic team immediately.');
    }
}