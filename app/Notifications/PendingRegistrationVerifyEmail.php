<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PendingRegistrationVerifyEmail extends Notification
{
    use Queueable;

    public function __construct(
        public string $name,
        public string $verificationUrl,
        public int $expiresInMinutes = 60,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $appName = config('app.name', 'MOA');

        $closingLine = config('services.sms.otp_enabled', false)
            ? 'Your account will not be created until both your email and mobile number are verified.'
            : 'Your account will not be created until you verify your email address.';

        return (new MailMessage)
            ->subject("Verify your {$appName} registration email")
            ->greeting("Hello {$this->name},")
            ->line("Please verify your email address to continue setting up your {$appName} patient account.")
            ->action('Verify Email Address', $this->verificationUrl)
            ->line("This link will expire in {$this->expiresInMinutes} minutes.")
            ->line($closingLine);
    }
}
