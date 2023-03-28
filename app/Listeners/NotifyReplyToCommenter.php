<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use App\Notifications\RepliedNotification;
use Illuminate\Support\Facades\URL;

class NotifyReplyToCommenter implements ShouldQueue
{
  use InteractsWithQueue;
  
  public function handle($event) {
    if ($event->reply->replier_id !== $event->comment->commenter_id) {
      if (getClassByType('video') === $event->comment->commentable_type) {
        $reply_page_link = config('frontend.url').'/video/'.$event->commentable->id.'/comments?highlight=reply&comment_id='.$event->comment->id.'&reply_id='.$event->reply->id;
        $data = [
          'type' => 'video',
          'replier_name' => $event->replier->channel->name,
          'text' => $event->reply->text,
          'video_link' => $event->commentable->link,
          'video_thumbnail_url' => $event->commentable->thumbnail_url,
          'video_title' => $event->commentable->title,
          'replier_channel_page_link' => $event->replier->channel->link,
          'replier_logo_url' => $event->replier->channel->logo_url,
          'reply_page_link' => $reply_page_link,
        ];
        if ($event->comment->commenter_id !== $event->replier->id && $event->comment->commenter->settings->data->notifications->replies) {
          $event->comment->commenter->notify(new RepliedNotification($data));
        }
      }
      
      else if (getClassByType('post') === $event->comment->commentable_type){
        
      }

    }
  }
}