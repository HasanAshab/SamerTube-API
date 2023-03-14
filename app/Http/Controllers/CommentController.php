<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Video;
use App\Models\Post;
use App\Models\Comment;

class CommentController extends Controller
{

  // Get all comments of a specific content
  public function index(Request $request, $type, $id) {
    $request->validate([
      'sort' => 'required|in:top,newest'
    ]);
    $Model = $this->getClassByType($type);
    $model = $Model::find($id);
    $isLoggedIn = auth()->check();
    if (($isLoggedIn && !$request->user()->can('readComments', [$Model, $model])) || $model->visibility !== 'public') {
      abort(405);
    }

    $relations = [
      'commenter' => function ($query) {
        $query->select('id', 'name', 'logo_url');
      }];
    if ($isLoggedIn) {
      $relations['reviewed'] = function ($query) {
        $query->select('id', 'review', 'reviewable_id', 'reviewer_id');
      };
    }
    $comment_query = $model->comments()->with($relations);
    if ($request->sort === 'top') {
      $comment_query->orderByDesc('like_count')->orderByDesc('heart')->orderByDesc('reply_count');
    }
    if (isset($request->limit)) {
      $offset = isset($request->offset)
      ?$request->offset
      :0;
      $comment_query->offset($offset)->limit($request->limit);
    }
    $comments = $comment_query->latest()->get();

    foreach ($comments as $comment) {
      $comment->creator = ($comment->commenter_id === $model->channel_id);
      $comment->author = $isLoggedIn || ($comment->commenter_id === auth()->id());
    }
    return $comments;
  }

  // Create comment on a content
  public function store(Request $request, $type, $id) {
    $request->validate([
      'text' => 'bail|required|string|max:300'
    ]);
    $Model = $this->getClassByType($type);
    $model = $Model::find($id);
    if (!auth()->user()->can('comment', [$Model, $model])) {
      abort(405);
    }
    $result = $model->comment($request->text);
    if ($result) {
      return ['success' => true];
    }
    return response()->json(['success' => false], 422);
  }

  // Update comment
  public function update(Request $request, $type, $id) {
    $request->validate([
      'text' => 'bail|required|string|max:300'
    ]);
    $Model = $this->getClassByType($type);
    $comment = Comment::find($id);
    if (!$request->user()->can('updateComment', [$Model, $comment])) {
      abort(405);
    }

    $result = $comment->update([
      'text' => $request->text
    ]);
    if ($result) {
      return ['success' => true];
    }
    return response()->json(['success' => false], 422);
  }

  // Delete a comment
  public function destroy($type, $id) {
    $Model = $this->getClassByType($type);
    $comment = Comment::find($id);
    if (auth()->user()->can('deleteComment', [$Model, $comment])) {
      if ($comment->delete()) {
        $comment->commentable->decrement('comment_count', 1);
        return ['success' => true,
          'message' => 'Comment successfully deleted!'];
      }
      return response()->json([
        'success' => false,
        'message' => 'Failed to delete comment!'
      ], 422);
    }
    abort(405);
  }

  // Get all comments of a video with 1 highlighted one. for notification view
  public function getCommentsWithHighlighted(Request $request, $video_id, $comment_id) {
    $video = Video::find($video_id);
    if (!$request->user()->can('readComments', [Video::class, $video])) {
      abort(405);
    }
    $comment_query = $video->comments();
    if (isset($request->limit)) {
      $offset = isset($request->offset)
      ?$request->offset
      :0;
      $comment_query->offset($offset)->limit($request->limit);
    }
    $comments = $comment_query->get();

    foreach ($comments as $comment) {
      $comment->review = $comment->reviewed();
      $comment->highlight = ($comment->id == $comment_id);
      $comment->author = ($comment->commenter_id === $video->channel_id);
    }
    return [
      'heading' => "Comments on &quot;".$video->title."&quot;",
      'link' => $video->link,
      'thumbnail_url' => $video->thumbnail_url,
      'comments' => $comments
    ];
  }

  // Give heart on a comment
  public function giveHeart($id) {
    $comment = Comment::find($id);
    if ($comment->commentable->channel_id !== auth()->id()) {
      abort(405);
    }
    $comment->heart = (int)!$comment->heart;
    $result = $comment->save();
    if ($result) {
      if ($comment->heart) {
        // notify
      }
      return response()->noContent();
    }
    return response()->json(['success' => false],
      422);
  }

  protected function getClassByType($type) {
    return match($type) {
      'video' => Video::class,
      'post' => Post::class,
      default => null
      };
    }
  }