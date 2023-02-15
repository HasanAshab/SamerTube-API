<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class Watched
{
  use Dispatchable, InteractsWithSockets, SerializesModels;

  public $user,
  $video_id;

  public function __construct($user, $video_id) {
    $this->user = $user;
    $this->video_id = $video_id;
  }
}