<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CustomNotification extends Notification implements ShouldQueue
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
    return ['mail'];
  }

  /**
  * Get the mail representation of the notification.
  *
  * @param  mixed  $notifiable
  * @return \Illuminate\Notifications\Messages\MailMessage
  */
  public function toMail($notifiable) {
    $notification = (new MailMessage)
    ->subject($this->data['subject'])
    ->line($this->data['description']);
    if(isset($this->data['greeting'])){
      $notification->greeting($this->data['greeting']);
    }
    if(isset($this->data['action_label']) && isset($this->data['action_url'])){
      $notification->action($this->data['action_label'], $this->data['action_url']);
    }
    if(isset($this->data['footer'])){
      $notification->line($this->data['footer']);
    }
    return $notification;
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