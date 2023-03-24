<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use App\Notifications\CommentedVideoNotification;
use Illuminate\Support\Facades\Notification;

class NotifyCommentToCreator implements ShouldQueue
{
  use InteractsWithQueue;

  public function handle($event) {
    if(getClassByType('video') === $event->comment->commentable_type){
      $video = $event->comment->commentable;
      $uploader = $video->uploader;
      $commenter = $event->comment->commenter->channel;
      $data = [
        'commenter_name' => $commenter->name,
        'text' => $event->comment->text,
        'video_link' => $video->link,
        'video_thumbnail_url' => $video->thumbnail_url,
        'video_title' => $video->title,
        'commenter_channel_page_link' => $commenter->link,
        'commenter_logo_url' => $commenter->logo_url,
        'reply_page_link' => URL::signedRoute('comments.highlighted', ['video_id' => $video->id, 'comment_id' => $event->comment->id]),
        'manage_comments_page_link' => ''
      ];
      if($uploader->id !== $event->comment->commenter_id && $uploader->settings->data->notifications->channel){
        $uploader->notify(new CommentedVideoNotification($data));
      }
    }
    
    else if(getClassByType('post') === $event->comment->commentable_type){
      
    }
  }
}