<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PostedNotification extends Notification
{
    use Queueable;
    protected $data;
    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($data)
    {
      $this->data = $data;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
      $text = strlen($this->data['post_text']) > 20
       ?$this->data['channel_name'].' '.substr($this->data['post_text'], 0, 20)'...'
       :$this->data['channel_name'].' '.$this->data['post_text'];
        return [
          'logo_url' => $this->data['channel_logo_url'],
          'text' => $text,
        ];
    }
}
