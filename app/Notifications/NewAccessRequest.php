<?php

namespace App\Notifications;

use App\Models\AccessRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewAccessRequest extends Notification
{
    use Queueable;

    public function __construct(private AccessRequest $accessRequest)
    {
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('New Access Request')
            ->line("New access request from {$this->accessRequest->name}")
            ->line("Email: {$this->accessRequest->email}")
            ->line("Reason: {$this->accessRequest->reason}")
            ->action('Review Request', url('/admin/access-requests'))
            ->line('Thank you for using our application!');
    }
}