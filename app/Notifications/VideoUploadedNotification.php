<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class VideoUploadedNotification extends Notification implements ShouldQueue
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
    return ['mail', 'database'];
  }

  /**
  * Get the mail representation of the notification.
  *
  * @param  mixed  $notifiable
  * @return \Illuminate\Notifications\Messages\MailMessage
  */
  public function toMail($notifiable) {
    return (new MailMessage)
      ->subject('New video from '.$this->data['channel_name'])
      ->view('emails.videoUploaded', $this->data);
  }

  /**
  * Get the array representation of the notification.
  *
  * @param  mixed  $notifiable
  * @return array
  */
  public function toArray($notifiable) {
    $channel_name = $this->data['channel_name'];
    $video_title = $this->data['video_title'];
    return [
      'text' => "$channel_name uploaded: $video_title",
      'channel_id' => $this->data['channel_id'],
      'channel_logo_url' => $this->data['channel_logo_url'],
      'video_thumbnail_url' => $this->data['video_thumbnail_url'],
      'link' => $this->data['link'],
    ];
  }
}