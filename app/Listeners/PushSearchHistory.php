<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use App\Events\Searched;
use App\Models\History;

class PushSearchHistory implements ShouldQueue
{
  use InteractsWithQueue;
    public function handle(Searched $event)
    {
      History::updateOrCreate(
        ['user_id' => $event->user->id, 'search_term' => $event->term],
        ['created_at' => now()]
      );
    }
}
