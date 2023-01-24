<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Notification;

class PublishVideo implements ShouldQueue
{
  use Dispatchable,
  InteractsWithQueue,
  Queueable,
  SerializesModels;
  protected $video,
  $notify;
  /**
  * Create a new job instance.
  *
  * @return void
  */
  public function __construct($video, $notify) {
    $this->video = $video;
    $this->notify = $notify;
  }

  /**
  * Execute the job.
  *
  * @return void
  */
  public function handle() {
    $this->video->update(['visibility' => 'public']);
    $this->video->channel->increment('total_videos', 1);
    if ($this->notify) {
      $text = $this->video->channel->name." uploaded: &quot;".$this->video->title."&quot;";
      $notification = new Notification;
      $notification->from = $this->video->channel_id;
      $notification->type = "video";
      $notification->text = $text;
      $notification->url = $this->video->link;
      $notification->logo_url = $this->video->channel->logo_url;
      $notification->thumbnail_url = $this->video->thumbnail_url;
      $notification->save();
    }
  }
}