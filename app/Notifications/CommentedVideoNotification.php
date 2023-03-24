<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

class CommentedVideoNotification extends Notification
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
    return ['mail',
      'database'];
  }

  /**
  * Get the mail representation of the notification.
  *
  * @param  mixed  $notifiable
  * @return \Illuminate\Notifications\Messages\MailMessage
  */
  public function toMail($notifiable) {
    return (new MailMessage)
    ->subject('New comment on "'.$this->data['video_title'].'"')
    ->view('emails.commentedVideo', $this->data);
  }
  /**
  * Get the array representation of the notification.
  *
  * @param  mixed  $notifiable
  * @return array
  */
  public function toArray($notifiable) {
    if (strlen($this->data['text']) > 15){
      $text = $this->data['commenter_name'].' commented: "'.substr($this->data['text'], 0, 15).'..."';
    }
    else {
      $text = $this->data['commenter_name'].' commented: "'.$this->data['text'].'"';
    }
    return [
      'commenter_logo_url' => $this->data['commenter_logo_url'],
      'video_thumbnail_url' => $this->data['video_thumbnail_url'],
      'text' => $text,
      'link' => $this->data['reply_page_link'],
    ];
  }
}