<?php

namespace App\Notifications;

/*use Illuminate\Contracts\Queue\ShouldQueue;*/
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SendOTP extends Notification /*implements ShouldQueue*/
{
    public function __construct(public string $otp, public string $type) {}

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('Your OTP Code for ' . ucfirst($this->type))
            ->line('Your OTP is: ' . $this->otp)
            ->line('This code will expire in 10 minutes.')
            ->line('If you did not request this, please ignore the email.');
    }
}
