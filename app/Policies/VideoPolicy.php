<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Video;
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
}