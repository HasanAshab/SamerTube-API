<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class Searched
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $user, $term;
    
    public function __construct($user, $term)
    {
        $this->user = $user;
        $this->term = $term;
    }
}
