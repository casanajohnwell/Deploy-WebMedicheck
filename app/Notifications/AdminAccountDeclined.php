<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AdminAccountDeclined extends Notification
{
    public function __construct(public string $healthCenter, public ?string $reason = null) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject('Your MediCheck administrator account was declined')
            ->greeting('Hello '.$notifiable->name.',')
            ->line("Your administrator account for {$this->healthCenter} was declined by the MHO.");

        if (filled($this->reason)) {
            $mail->line('Reason: '.$this->reason);
        }

        return $mail->line('If you think this is a mistake, please contact the Municipal Health Office.');
    }
}
