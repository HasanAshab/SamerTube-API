<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Post;
use App\Models\Comment;
use Illuminate\Auth\Access\HandlesAuthorization;

class PostPolicy
{
  use HandlesAuthorization;
  
  public function create(User $user) {
    return $user->is_admin || $user->channel->post_unlocked;
  }
  
  public function read(User $user, Post $post){
    return $user->id === $post->channel_id || $post->visibility === 'public';
  }
  
  public function update(User $user, Post $post) {
    return $user->id === $post->channel_id && in_array($post->type, ['text', 'shared']);
  }
  
  public function delete(User $user, Post $post) {
    return $user->is_admin || $user->id === $post->channel_id;
  }
  
  public function report(User $user, Post $post){
    return $this->read($user, $post);
  }
  
  public function review(User $user, Post $post){
    return $post->type !== 'shared' && ($user->is_admin || $post->visibility === "public" || $post->channel_id === $user->id);
  }
  
  public function comment(User $user, Post $post){
    return $post->type !== 'shared' && ($user->is_admin || $post->visibility === "public" || $post->channel_id === $user->id);
  }
  
  public function readComments(User $user, Post $post){
    return $user->is_admin || $post->visibility === "public" || $post->channel_id === $user->id;
  }
  
  public function updateComment(User $user, Comment $comment){
    return $user->id === $comment->commenter_id;
  }
  
  public function deleteComment(User $user, Comment $comment){
    return $user->is_admin || $user->id === $comment->commenter_id || $comment->commentable->channel_id === $user->id;
  }
  
  public function vote(User $user, Post $post){
    return $user->id !== $post->channel_id && ($post->visibility === 'public' || $user->is_admin) && in_array($post->type, ['text_poll', 'image_poll']);
  }
}