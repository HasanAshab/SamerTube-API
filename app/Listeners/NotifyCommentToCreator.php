<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use App\Notifications\CommentedNotification;
use Illuminate\Support\Facades\URL;
use App\Models\Video;
use App\Models\Post;

class NotifyCommentToCreator implements ShouldQueue
{
  use InteractsWithQueue;

  public function handle($event) {
    $publisher = $event->comment->commentable->publisher;
    $commenter_channel = $event->comment->commenter->channel;
    if ($event->comment->commenter_id === $event->comment->commentable->channel_id || !$publisher->settings->data->notifications->channel) {
      return null;
    }
    if ($event->comment->commentable instanceof Video) {
      $video = $event->comment->commentable;
      $reply_page_link = config('frontend.url').'/video/'.$video->id.'/comments?highlight=comment&comment_id='.$event->comment->id;
      $manage_comments_page_link = config('frontend.studio.url')."/video/$video->id/manage-comments";
      $data = [
        'type' => 'video',
        'commenter_name' => $commenter_channel->name,
        'text' => $event->comment->text,
        'video_link' => $video->link,
        'video_thumbnail_url' => $video->thumbnail_url,
        'video_title' => $video->title,
        'commenter_channel_page_link' => $commenter_channel->link,
        'commenter_logo_url' => $commenter_channel->logo_url,
        'reply_page_link' => $reply_page_link,
        'manage_comments_page_link' => $manage_comments_page_link
      ];
    } 
    else if ($event->comment->commentable instanceof Post) {
      $post = $event->comment->commentable;
      $post_image_url = $post->files()->first()->link;
      $reply_page_link = config('frontend.url').'/post/'.$post->id.'/comments?highlight=comment&comment_id='.$event->comment->id;
      $manage_comments_page_link = config('frontend.studio.url')."/post/$post->id/manage-comments";
      $post_content = strlen($post->content) > 13
        ?substr($post->content, 0, 13).'...'
        :$post->content;
      $data = [
        'type' => 'post',
        'commenter_name' => $commenter_channel->name,
        'text' => $event->comment->text,
        'post_content' => $post_content,
        'post_image_url' => $post_image_url,
        'commenter_channel_page_link' => $commenter_channel->link,
        'commenter_logo_url' => $commenter_channel->logo_url,
        'reply_page_link' => $reply_page_link,
        'manage_comments_page_link' => $manage_comments_page_link
      ];
 
    }
    $publisher->notify(new CommentedNotification($data));
  }
}