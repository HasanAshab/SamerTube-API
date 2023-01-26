<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Video;
use App\Models\Reply;
use Illuminate\Auth\Access\HandlesAuthorization;

class ReplyPolicy
{
  use HandlesAuthorization;

  public function create(User $user, Comment $comment){
    return $user->is_admin || $comment->video->visibility === "public" || $comment->video->channel_id === $user->id;
  }
  
  public function read(User $user, Comment $comment){
    return $comment->video->allow_comments && ($user->is_admin || $comment->video->visibility === "public" || $comment->video->channel_id === $user->id);
  }
  
  public function update(User $user, Reply $reply){
    return $user->id === $reply->replier_id;
  }
  
  public function delete(User $user, Reply $reply){
    return $user->is_admin || $user->id === $reply->replier_id || $reply->video->channel_id === $user->id;
  }
}