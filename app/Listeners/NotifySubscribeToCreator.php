<?php

namespace App\Listeners;

use App\Events\Subscribed;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use App\Notifications\SubscribedNotification;

class NotifySubscribeToCreator implements ShouldQueue
{
  use InteractsWithQueue;
  
  public function handle(Subscribed $event) {
    $data = [
      'subscriber_name' => $event->subscriber->channel->name,
      'subscriber_logo_url' => $event->subscriber->channel->logo_url,
      'subscriber_sub_count' => $event->subscriber->channel->total_subscribers,
      'subscriber_channel_page_link' => $event->subscriber->channel->link
    ];
    $creator = $event->subscribe->user;
    if($creator->settings->data->notifications->channel){
      $creator->notify(new SubscribedNotification($data));
    }
  }

}