<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PasswordResetCode extends Notification
{
    public function __construct(public string $code, public int $minutes) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('MediCheck password reset code')
            ->greeting('Hello '.$notifiable->name.',')
            ->line('Use this code to reset your MediCheck password:')
            ->line('**'.$this->code.'**')
            ->line("The code expires in {$this->minutes} minutes.")
            ->line('If you did not ask to reset your password, you can ignore this email.');
    }
}
