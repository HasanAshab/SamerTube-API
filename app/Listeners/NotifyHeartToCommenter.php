<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use App\Notifications\CommentHeartedNotification;
use App\Models\Video;
use App\Models\Post;
use App\Models\Comment;
use App\Models\Reply;
use Illuminate\Support\Facades\Log;

class NotifyHeartToCommenter implements ShouldQueue
{
  use InteractsWithQueue;
  public function handle($event) {
    if($event->comment->isCreator()){
      return null;
    }
    if ($event->comment instanceof Comment) {
      if ($event->comment->commentable instanceof Video) {
        $video = $event->comment->commentable;
        $highlight_page_link = config('frontend.url').'/video/'.$video->id.'/comments?highlight=comment&comment_id='.$event->comment->id;
        $data = [
          'creator_channel_name' => $video->channel->name,
          'creator_logo_url' => $video->channel->logo_url,
          'video_title' => $video->title,
          'video_thumbnail_url' => $video->thumbnail_url,
          'video_link' => $video->link,
          'commenter_name' => $event->comment->commenter->channel->name,
          'commenter_channel_link' => $event->comment->commenter->channel->link,
          'commenter_logo_url' => $event->comment->commenter->channel->logo_url,
          'highlight_page_link' => $highlight_page_link,
          'text' => $event->comment->text,
        ];
        $event->comment->commenter->notify(new CommentHeartedNotification($data));
      }
    } 
    else if ($event->comment instanceof Reply) {
      $reply = $event->comment;
      if ($reply->comment->commentable instanceof Video) {
        $video = $reply->comment->commentable;
        $highlight_page_link = config('frontend.url').'/video/'.$video->id.'/comments?highlight=reply&comment_id='.$reply->comment_id.'&reply_id='.$reply->id;
        $data = [
          'creator_channel_name' => $video->channel->name,
          'creator_logo_url' => $video->channel->logo_url,
          'video_title' => $video->title,
          'video_thumbnail_url' => $video->thumbnail_url,
          'video_link' => $video->link,
          'commenter_name' => $reply->replier->channel->name,
          'commenter_channel_link' => $reply->replier->channel->link,
          'commenter_logo_url' => $reply->replier->channel->logo_url,
          'highlight_page_link' => $highlight_page_link,
          'text' => $reply->text,
        ];
        $reply->replier->notify(new CommentHeartedNotification($data));
      }
    }
  }
}