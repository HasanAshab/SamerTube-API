<?php

namespace App\Listeners;

use App\Events\Posted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use App\Notifications\PostedNotification;
use Illuminate\Support\Facades\Notification;

class NotifyPostToSubscribers implements ShouldQueue{
  use InteractsWithQueue;
  
  public function handle(Posted $event) {
    $channel = $event->post->channel;
    $data = [
      'channel_name' => $channel->name,
      'channel_logo_url' => $channel->logo_url,
      'post_text' => $event->post->title,
    ];
    $channel->load('subscribers.user.settings');
    $notifiable_subscribers = $channel->subscribers->filter(function($subscriber){
      $subscriptions_notification_enabled = $subscriber->user->settings->data->notifications->subscriptions;
      return $subscriber->preference === 'all' && $subscriptions_notification_enabled;
    });
    $notifiable_users = $notifiable_subscribers->pluck('user');
    Notification::send($notifiable_users, new PostNotification($data));
  }
}