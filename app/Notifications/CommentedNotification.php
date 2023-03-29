<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;
use App\Models\User;

class CommentedNotification extends Notification
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
    ->subject('New comment on "'.$this->data['video_title'].'"')
    ->view('emails.commented', $this->data);
  }
  /**
  * Get the array representation of the notification.
  *
  * @param  mixed  $notifiable
  * @return array
  */
  public function toArray($notifiable) {
    if (strlen($this->data['text']) > 15) {
      $text = $this->data['commenter_name'].' commented: "'.substr($this->data['text'], 0, 15).'..."';
    } else {
      $text = $this->data['commenter_name'].' commented: "'.$this->data['text'].'"';
    }
    return [
      'logo_url' => $this->data['commenter_logo_url'],
      'thumbnail_url' => $this->data['video_thumbnail_url'],
      'text' => $text,
      'link' => $this->data['reply_page_link'],
    ];
  }
}