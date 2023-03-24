<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use App\Notifications\VideoUploadedNotification;
use Illuminate\Support\Facades\Notification;

class NotifyVideoToSubscribers
{
  use InteractsWithQueue;

  public function handle($event) {
    $channel = $event->video->channel;
    $data = [
      'channel_id' => $channel->id,
      'channel_name' => $channel->name,
      'channel_logo_url' => $channel->logo_url,
      'video_title' => $event->video->title,
      'video_description' => substr($event->video->description, 0, 20).'...',
      'video_thumbnail_url' => $event->video->thumbnail_url,
      'link' => $event->video->link, 
    ];
    $channel->load('subscribers.user.settings');
    $notifiable_subscribers = $channel->subscribers->filter(function($subscriber){
      $subscriptions_notification_enabled = $subscriber->user->settings->data->notifications->subscriptions;
      return $subscriber->preference === 'all' && $subscriptions_notification_enabled;
    });
    $notifiable_users = $notifiable_subscribers->pluck('user');
    Notification::send($notifiable_users, new VideoUploadedNotification($data));
  }
}