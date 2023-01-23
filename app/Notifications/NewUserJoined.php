<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\User;
class NewUserJoined extends Notification implements ShouldQueue
{
  use Queueable;
  private $user;
  /**
  * Create a new notification instance.
  *
  * @return void
  */
  public function __construct($user) {
    $this->user = $user;
  }

  /**
  * Get the notification's delivery channels.
  *
  * @param  mixed  $notifiable
  * @return array
  */
  public function via($notifiable) {
    return ['mail'];
  }

  /**
  * Get the mail representation of the notification.
  *
  * @param  mixed  $notifiable
  * @return \Illuminate\Notifications\Messages\MailMessage
  */
  public function toMail($notifiable) {
    return (new MailMessage)
    ->line('A new account is registerd just now!')
    ->line('Email: '.$this->user->email)
    ->line('The total users of our website is now '.User::query()->count())
    ->action('Check Dashboard', url('/api/admin/dashboard'))
    ->line('Thanks for reading');
  }

  /**
  * Get the array representation of the notification.
  *
  * @param  mixed  $notifiable
  * @return array
  */
  public function toArray($notifiable) {
    return [
      //
    ];
  }
}