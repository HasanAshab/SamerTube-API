<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Video;
use App\Models\Comment;
use Illuminate\Auth\Access\HandlesAuthorization;

class VideoPolicy
{
  use HandlesAuthorization;
  
  public function watch(User $user, Video $video){
    return $user->is_admin || $video->visibility === 'public' || $video->channel_id !== $user->id;
  }
  public function update(User $user, Video $video) {
    return $user->id === $video->channel_id;
  }
  public function delete(User $user, Video $video) {
    return $user->is_admin || $user->id === $video->channel_id;
  }
  
  public function report(User $user, Video $video){
    return $this->watch($user, $video);
  }
 
  public function review(User $user, Video $video){
    return $user->is_admin || $video->visibility === "public" || $video->channel_id === $user->id;
  }
  
  public function comment(User $user, Video $video){
    return $video->allow_comments && ($user->is_admin || $video->visibility === "public" || $video->channel_id === $user->id);
  }
  
  public function readComments(User $user, Video $video){
    return $video->allow_comments && ($user->is_admin || $video->visibility === "public" || $video->channel_id === $user->id);
  }
  
  public function updateComment(User $user, Comment $comment){
    return $user->id === $comment->commenter_id;
  }
  
  public function deleteComment(User $user, Comment $comment){
    return $user->is_admin || $user->id === $comment->commenter_id || $comment->commentable->channel_id === $user->id;
  }
}