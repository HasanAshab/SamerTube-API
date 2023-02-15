<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use App\Events\Watched;
use App\Models\History;
class PushWatchHistory implements ShouldQueue
{
  use InteractsWithQueue;
    public function handle(Watched $event)
    {
      History::updateOrCreate(
        ['user_id' => $event->user->id, 'video_id' => $event->video_id],
        ['created_at' => now()]
      );
    }
}
