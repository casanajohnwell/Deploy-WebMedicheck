<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EmailChangeCode extends Notification
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
            ->subject('MediCheck email confirmation code')
            ->line('Use this code to confirm your new MediCheck email address:')
            ->line('**'.$this->code.'**')
            ->line("The code expires in {$this->minutes} minutes.")
            ->line('If you did not ask to change your email, you can ignore this email.');
    }
}
