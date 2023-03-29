<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\User;

class SubscribedNotification extends Notification
{
  use Queueable;

  protected $data;
  /**
  * Create a new notification instance.
  *
  * @return void
  */
  public function __construct($data) {
    $this->data = $data;
  }

  /**
  * Get the notification's delivery channels.
  *
  * @param  mixed  $notifiable
  * @return array
  */
  public function via($notifiable) {
    $channels = ['database'];
    $user = $notifiable instanceof User ? $notifiable : $notifiable->user;
    if ($user->settings->data->notifications->mail) {
      $channels[] = 'mail';
    }
    return $channels;
  }

  /**
  * Get the mail representation of the notification.
  *
  * @param  mixed  $notifiable
  * @return \Illuminate\Notifications\Messages\MailMessage
  */
  public function toMail($notifiable) {
    return (new MailMessage)
    ->subject($this->data['subscriber_name'].' has subscribed to you on '.config('app.name'))
    ->view('emails.subscribed', $this->data);
  }

  /**
  * Get the array representation of the notification.
  *
  * @param  mixed  $notifiable
  * @return array
  */
  public function toArray($notifiable) {
    $text = 'New subscriber: '.$this->data['subscriber_name'];
    return [
      'text' => $text,
      'logo_url' => $this->data['subscriber_logo_url'],
      'link' => $this->data['subscriber_channel_page_link'],
    ];
  }
}