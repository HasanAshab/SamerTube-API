<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class Subscribed
{
  use Dispatchable, InteractsWithSockets, SerializesModels;
  public $subscribe, $subscriber;

  public function __construct($subscribe) {
    $this->subscribe = $subscribe;
    $this->subscriber = $subscribe->subscriber;
  }
}