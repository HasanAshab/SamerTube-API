<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class Replied
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $reply, $comment, $commentable, $replier;
    
    public function __construct($reply){
      $this->reply = $reply;
      $this->comment = $reply->comment;
      $this->commentable = $this->comment->commentable;
      $this->replier = $reply->replier;
    }
}
