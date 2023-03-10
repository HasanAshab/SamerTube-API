<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Comment;
use App\Models\Reply;

class ReplyController extends Controller
{
  
  // Get all replies of a specific comment
  public function index(Request $request, $id) {
    $comment = Comment::find($id);
    $isLoggedIn = auth()->check();
    if (($isLoggedIn && !$request->user()->can('read', [Reply::class, $comment])) || $comment->commentable->visibility !== 'public') {
      abort(405);
    }
    
    if($isLoggedIn){
      $relations = [
        'replier' => function ($query){
          $query->select('id', 'name', 'logo_url');
        },
        'reviewed' => function ($query) use ($isLoggedIn){
          $query->select('id', 'review');
        }
      ];
    }
    else{
      $relations = [
        'replier' => function ($query){
          $query->select('id', 'name', 'logo_url');
        }
      ];
    }
    $reply_query = $comment->replies()->with($relations);
    if (isset($request->limit)) {
      $offset = isset($request->offset)
      ?$request->offset
      :0;
      $reply_query->offset($offset)->limit($request->limit);
    }
    $replies = $reply_query->orderByDesc('heart')->orderByDesc('like_count')->latest()->get();
    foreach ($replies as $reply) {
      $reply->creator = ($reply->replier_id === $comment->commentable->channel_id);
      $reply->author = $isLoggedIn || ($reply->replier_id === auth()->id());
    }
    return $replies;
  }

  // Create reply on a comment
  public function store(Request $request, $id) {
    $request->validate([
      'text' => 'bail|required|string|max:300'
    ]);
    $comment = Comment::find($id);
    if (!$request->user()->can('create', [Reply::class, $comment])) {
      abort(405);
    }
    $reply = Reply::create([
      'text' => $request->text,
      'comment_id' => $id,
    ]);
    if ($reply) {
      $comment->increment('reply_count', 1);
      return ['success' => true];
    }
    return response()->json(['success' => false], 451);
  }

  // Update a reply
  public function update(Request $request, $id) {
    $request->validate([
      'text' => 'bail|required|string|max:300'
    ]);
    $reply = Reply::find($id);

    if (!$request->user()->can('update', [Reply::class, $reply])) {
      abort(405);
    }
    $reply->text = $request->text;
    $result = $comment->save();
    if ($result) {
      return response()->noContent();
    }
    return response()->json(['success' => false], 451);
  }

  // Delete a reply
  public function destroy($id) {
    $reply = Reply::find($id);
    if (auth()->user()->can('delete', [Reply::class, $reply])) {
      $result = $reply->delete();
      if ($result) {
        $reply->comment->decrement('reply_count', 1);
        return ['success' => true,
          'message' => 'reply successfully deleted!'];
      }
      return response()->json([
        'success' => false,
        'message' => 'Failed to delete reply!'
      ], 451);
    }
    abort(405);
  }
  
  // Give heart on a reply
  public function giveHeart($id) {
    $reply = Reply::find($id);
    if ($reply->comment->commentable->channel_id !== auth()->id()) {
      abort(405);
    }
    $reply->heart = (int)!$reply->heart;
    $result = $reply->save();
    if ($result) {
      if ($reply->heart) {
        // notify
      }
      return response()->noContent();
    }
    return response()->json(['success' => false],
      422);
  }
}