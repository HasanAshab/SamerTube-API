<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class PublishPost implements ShouldQueue
{
  use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
  protected $post, $notify;
  /**
  * Create a new job instance.
  *
  * @return void
  */
  public function __construct($post, $notify) {
    $this->post = $post;
    $this->notify = false;//$notify;
  }

  /**
  * Execute the job.
  *
  * @return void
  */
  public function handle() {
    $this->post->update(['visibility' => 'public']);
    if ($this->notify) {
      $text = $this->post->channel->name." posted: &quot;".$this->video->title."&quot;";
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