<?php
namespace App\Traits;
use App\Models\Comment;

trait CommentUtility {

  public static function bootCommentUtility() {
    static::deleting(function ($model) {
      $model->comments()->delete();
    });
  }

  public function comments() {
    return $this->morphMany(Comment::class, 'commentable')->join('channels', 'channels.id', '=', 'comments.commenter_id')->select('comments.*', 'channels.name', 'channels.logo_url');
  }

  public function comment($text) {
    $comment = Comment::create([
      'commentable_type' => get_class($this),
      'commentable_id' => $this->id,
      'text' => $text
    ]);
    $this->increment('comment_count', 1);
    return $comment;
  }

  public function commented() {
    $user_id = auth()->id();
    return Comment::where('commenter_id', $user_id)->where('commentable_type', get_class($this))->where('commentable_id', $this->id)->get();
  }
}