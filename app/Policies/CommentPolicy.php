<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Video;
use App\Models\Comment;
use Illuminate\Auth\Access\HandlesAuthorization;

class CommentPolicy
{
  use HandlesAuthorization;

  public function create(User $user, Video $video){
    return $video->allow_comments && ($user->is_admin || $video->visibility === "public" || $video->channel_id === $user->id);
  }
  
  public function read(User $user, Video $video){
    return $video->allow_comments && ($user->is_admin || $video->visibility === "public" || $video->channel_id === $user->id);
  }
  
  public function update(User $user, Comment $comment){
    return $user->id === $comment->commenter_id;
  }
  
  public function delete(User $user, Comment $comment){
    return $user->is_admin || $user->id === $comment->commenter_id || $comment->commentable->channel_id === $user->id;
  }
}