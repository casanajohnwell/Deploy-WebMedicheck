<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AdminAccountApproved extends Notification
{
    public function __construct(public string $healthCenter) {}

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
            ->subject('Your MediCheck administrator account was approved')
            ->greeting('Hello '.$notifiable->name.',')
            ->line("Your administrator account for {$this->healthCenter} was approved by the MHO.")
            ->line('You can now sign in with the email and password you registered.')
            ->action('Sign in to MediCheck', route('login'));
    }
}
