<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use App\Models\Video;
use App\Models\Category;
use App\Models\History;
use App\Models\Review;
use App\Models\CommentReview;
use App\Models\ReplyReview;
use App\Models\Channel;
use App\Models\Comment;
use App\Models\Reply;
use App\Models\Subscriber;
use App\Models\Notification;
use App\Models\Hidden;
use App\Models\Playlist;
use App\Models\PlaylistVideo;
use App\Models\SavedPlaylist;
use Carbon\Carbon;

class videoApi extends Controller
{
  // Max data return per request [Pagination]
  protected $maxDataPerRequest = 20;

  //Get all public videos
  public function explore(Request $request) {
    return ($request->user()->is_admin)
    ?Video::where('visibility', 'public')->channel(['name', 'logo_url'])->rank()->cursorPaginate($this->maxDataPerRequest)
    :Video::query()->channel(['name', 'logo_url'])->rank()->cursorPaginate($this->maxDataPerRequest);
  }

  // Save a new video
  public function store(Request $request) {
    $request->validate([
      'title' => 'bail|required|string|between:1,72',
      'description' => 'bail|required|string|max:300',
      'visibility' => 'bail|required|in:public,private',
      'category' => 'bail|required|exists:categories,id',
      'video' => 'bail|required|mimes:mp4',
      'thumbnail' => 'bail|required|image'
    ]);
    $video = new Video;
    $video->channel_id = $request->uploader_id; //user()->id;
    $video->title = $request->title;
    $video->description = $request->description;
    $video->category = $request->category;
    $video->visibility = $request->visibility;
    $video->link = URL::signedRoute('video.watch', ['id' => $video->getNextId()]);
    require_once(storage_path('getID3/getid3/getid3.php'));
    $getID3 = new \getID3;
    $video->duration = $getID3->analyze($request->file('video'))['playtime_string'];
    $video->video_path = $this->upload('videos', $request->file('video'));
    $video->thumbnail_path = $this->upload('thumbnails', $request->file('thumbnail'));
    $video->video_url = URL::signedRoute('file.serve', ['type' => 'video', 'id' => $video->getNextId()]);
    $video->thumbnail_url = URL::signedRoute('file.serve', ['type' => 'thumbnail', 'id' => $video->getNextId()]);
    $result = $video->save();
    if ($result) {
      $video->channel->increment('total_videos', 1);
      $text = $video->channel->name." uploaded: &quot;".$request->title."&quot;";
      $notification = new Notification;
      $notification->from = $video->channel_id;
      $notification->type = "video";
      $notification->text = $text;
      $notification->url = $video->link;
      $notification->logo_url = $video->channel->logo_url;
      $notification->thumbnail_url = $video->thumbnail_url;
      $notification->save();
      return ['success' => $result,
        'message' => 'Video successfully uploaded!'];
    }
    return ['success' => $result,
      'message' => 'Failed to upload video!'];

  }

  // update own video
  public function update(Request $request, $id) {
    $request->validate([
      'title' => 'bail|required|string|between:1,72',
      'description' => 'bail|required|string|max:300',
      'visibility' => 'bail|required|in:public,private',
      'category' => 'bail|required|between:1,9',
      'thumbnail' => 'image'
    ]);
    $video = Video::find($id);
    if ($video->channel_id !== $request->user()->id) {
      return accessDenied();
    }
    $video->title = $request->title;
    $video->description = $request->description;
    $video->visibility = $request->visibility;
    $video->category = $request->category;
    if ($request->file('thumbnail') !== null) {
      $this->clear($video->thumbnail_path);
      $video->thumbnail_path = $this->upload('thumbnails', $request->file('thumbnail'));
    }
    $result = $video->save();

    if ($result) {
      return ['success' => true,
        'message' => 'Video successfully updated!'];
    }
    return response()->json([
      'success' => false,
      'message' => 'Failed to update video!'
    ], 451);

  }

  // Get details of a video to watch
  public function watch(Request $request, $id) {
    $video = Video::where('id', $id)->channel(['name', 'logo_url', 'total_subscribers'])->first();
    if (!$request->user()->is_admin && $video->visibility === 'private' && $video->channel_id !== $request->user()->id) {
      return accessDenied();
    }
    $old_history = History::where('user_id', $request->user()->id)->where('type', 'video')->where('history', $id)->first();
    if ($old_history !== null) {
      $old_history->delete();
    }
    $history = new History;
    $history->user_id = $request->user()->id;
    $history->type = 'video';
    $history->history = $id;
    $history->save();
    $video->author = ($video->channel_id === $request->user()->id);
    $video->subscribed = Subscriber::where('subscriber_id', $request->user()->id)->where('channel_id', $video->channel_id)->exists();
    return $video;
  }

  // Increase views of a video, when a user watched a video more than 10 percent
  public function increaseView($id) {
    $result = Video::find($id)->increment('view_count', 1);
    return $result
    ?['success' => true]
    :response()->json(['success' => false], 451);
  }

  // Delete own video
  public function destroy($id) {
    $video = Video::find($id);

    if (!auth()->user()->is_admin && $video->channel_id !== auth()->user()->id) {
      return accessDenied();
    }
    $r3 = $video->delete();
    $r1 = $this->clear($video->video_path);
    $r2 = $this->clear($video->thumbnail_path);
    if ($r1 && $r2 && $r3) {
      $video->channel->decrement('total_videos', 1);
      return ['success' => true,
        'message' => 'Video successfully deleted!'];
    }
    return response()->json([
      'success' => false,
      'message' => 'Failed to delete video!'
    ], 451);
  }

  // Get all video categories
  public function category() {
    return Category::all();
  }

  // Get search suggestion
  public function suggestions(Request $request, $query = null) {
    $history = History::where('user_id', $request->user()->id)->where('history', 'like', $query.'%')->where('type', 'search')->limit(20)->get(['id', 'history']);
    $suggestions = array();
    foreach ($history as $title) {
      array_push($suggestions, array('history' => true, 'id' => $title->id, 'suggestion' => $title->history));
    }
    if ($query === null) {
      return $suggestions;
    }
    $titles = Video::when(!$request->user()->is_admin, function ($query) {
      return $query->where('visibility', 'public');
    })->where('title', 'like', $query.'%')->rank()->limit(10)->get('title');
    foreach ($titles as $title) {
      array_push($suggestions, array('history' => false, 'suggestion' => $title->title));
    }
    return $suggestions;
  }

  // Search from public videos
  public function search(Request $request, $query = null) {
    $request->validate([
      'category' => 'exists:categories,id',
      'sort' => 'bail|required|string|in:relevance,view,date,rating',
      'date_range' => 'bail|required|string|in:anytime,hour,day,week,month,year',
    ]);
    if ($query === null) {
      $videos = Video::when(!$request->user()->is_admin, function ($query) {
        $query->where('visibility', 'public');
      })->when($request->category !== null, function ($query) use($request) {
        $query->where('category', $request->category);
      })->channel(['name', 'logo_url'])->rank($request->sort, $request->date_range)->cursorPaginate($this->maxDataPerRequest);
      return $videos;
    }
    $videos = Video::where('title', 'like', $query.'%')->when($request->user()->is_admin, function ($query) {
      $query->where('visibility', 'public');
    })->channel(['name', 'logo_url'])->rank($request->sort, $request->date_range)->cursorPaginate($this->maxDataPerRequest);
    $old_history = History::where('user_id', $request->user()->id)->where('type', 'search')->where('history', $query)->first();
    if ($old_history !== null) {
      $old_history->delete();
    }
    $history = new History;
    $history->user_id = $request->user()->id;
    $history->type = 'search';
    $history->history = $query;
    $history->save();
    $videos = Video::where('visibility', 'public')->where('title', 'like', $query.'%')->channel(['name', 'logo_url'])->rank($request->sort, $request->date_range)->cursorPaginate($this->maxDataPerRequest);

    return $videos;
  }

  //Get watch history
  public function watchHistory(Request $request) {
    $id = $request->user()->id;
    $dates = History::where('user_id', $id)->where('type', 'video')->distinct('date')->cursorPaginate($this->maxDataPerRequest, ['date']);
    $histories = array('data' => array());
    foreach ($dates->all() as $date) {
      $date_based_histories = History::where('user_id', $id)->where('type', 'video')->where('date', $date->date)->latest()->get('history');
      $videos = array();
      foreach ($date_based_histories as $history) {
        array_push($videos, $history->video);
      }
      array_push($histories['data'], ['date' => Carbon::parse($date->date)->format('jS M, Y'), 'videos' => $videos]);
    }
    $histories['next_page_url'] = $dates['next_page_url'];
    $histories['prev_page_url'] = $dates['prev_page_url'];
    return $histories;
  }

  // Like and dislike on a video
  public function postReview(Request $request, $video_id) {
    $request->validate([
      'review' => 'bail|required|in:0,1'
    ]);
    $video = Video::find($video_id);

    if ($video->visibility === "private" && $video->channel_id !== $reviewer_id) {
      return accessDenied();
    }
    $reviewer_id = $request->user()->id;
    $old_review = Review::where('video_id', $video_id)->where('reviewer_id', $reviewer_id)->first();
    if ($old_review !== null) {
      if ($old_review->review === $request->review) {
        $result = $old_review->delete();
      } else {
        $old_review->review = $request->review;
        $result = $old_review->save();
      }
    } else {
      $review = new Review;
      $review->reviewer_id = $reviewer_id;
      $review->video_id = $video_id;
      $review->review = $request->review;
      $result = $review->save();
    }
    if ($result) {
      $video->like_count = Review::where([['video_id', $video_id], ['review', 1]])->count();
      $video->dislike_count = Review::where([['video_id', $video_id], ['review', 0]])->count();
      $video->save();
      return ['success' => true];
    }
    return response()->json(['success' => false], 451);
  }

  // Get what is the review of user
  public function getReview(Request $request, $video_id) {
    $review = Review::where('video_id', $video_id)->where('reviewer_id', $request->user()->id);
    if ($review === null) {
      return ['success' => true,
        'review' => null];
    }
    $review_code = $review->value('review');
    return ['success' => true,
      'review' => $review_code];
  }

  // Post comment on a video
  public function postComment(Request $request, $video_id) {
    $request->validate([
      'text' => 'bail|required|string|max:300'
    ]);
    $commenter_id = $request->user()->id;
    $video = Video::find($video_id);
    if ($video->visibility === "private" && $video->channel_id !== $commenter_id) {
      return accessDenied();
    }
    $comment = new Comment;
    $comment->commenter_id = $commenter_id;
    $comment->text = $request->text;
    $comment->video_id = $video_id;
    $result = $comment->save();
    if ($result) {
      $video->increment('comment_count', 1);
      $video->channel->increment('total_comments', 1);
      if ($video->channel_id !== $commenter_id) {
        $channel = Channel::find($commenter_id, ['name', 'logo_url']);
        $text = $channel->name." commented: &quot;".$comment->text."&quot;";
        $notification = new Notification;
        $notification->from = $commenter_id;
        $notification->for = $video->channel_id;
        $notification->type = "comment";
        $notification->url = URL::signedRoute('comments.highlighted', ['video_id' => $video->id, 'comment_id' => $comment->id]);
        $notification->text = $text;
        $notification->logo_url = $channel->logo_url;
        $notification->thumbnail_url = $video->thumbnail_url;
        $notification->save();
      }
      return ['success' => true];
    }
    return response()->json(['success' => false], 451);
  }

  // Get all comments of a specific video
  public function getComments(Request $request, $video_id) {
    $video = Video::find($video_id);
    if (!$request->user()->is_admin && ($video->visibility === "private" && $video->channel_id !== $request->user()->id)) {
      return accessDenied();
    }
    $comments = $video->comments;
    foreach ($comments as $comment) {
      $comment->review = CommentReview::where([['comment_id', $comment->id], ['reviewer_id', $request->user()->id]])->value('review');
      $comment->author = ($comment->commenter_id === $video->channel_id);
    }
    return $comments;
  }

  // Get all comments of a video with 1 highlighted one. for notification view
  public function getCommentsWithHighlighted(Request $request, $video_id, $comment_id) {
    $video = Video::find($video_id);
    if (!$request->user()->is_admin && ($video->visibility === "private" && $video->channel_id !== $request->user()->id)) {
      return accessDenied();
    }
    $comments = $video->comments;
    foreach ($comments as $comment) {
      $comment->review = CommentReview::where([['comment_id', $comment->id], ['reviewer_id', $request->user()->id]])->value('review');
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

  // Update comment of a video
  public function updateComment(Request $request, $comment_id) {
    $request->validate([
      'text' => 'bail|required|string|max:300'
    ]);
    $comment = Comment::find($comment_id);
    if ($comment->commenter_id !== $request->user()->id) {
      return accessDenied();
    }
    $comment->text = $request->text;
    $result = $comment->save();
    if ($result) {
      return ['success' => true];
    }
    return response()->json(['success' => false], 451);
  }

  // Delete a comment
  public function removeComment(Request $request, $comment_id) {
    $comment = Comment::find($comment_id);
    $user_id = $request->user()->id;
    if ($request->user()->is_admin || $comment->commenter_id === $user_id || $comment->video->channel_id === $user_id) {
      $result = $comment->delete();
      if ($result) {
        $comment->video->decrement('comment_count', 1);
        $comment->video->channel->decrement('total_comments', 1);
        return ['success' => true,
          'message' => 'Comment successfully deleted!'];
      }
      return response()->json([
        'success' => false,
        'message' => 'Failed to delete comment!'
      ], 451);
    }
    return accessDenied();
  }

  // Like or Dislike a comment
  public function postCommentReview(Request $request, $comment_id) {
    $request->validate([
      'review' => 'bail|required|in:0,1'
    ]);
    $comment = Comment::find($comment_id);

    $reviewer_id = $request->user()->id;
    if ($comment->video->visibility === "private" && $comment->video->channel_id !== $reviewer_id) {
      return accessDenied();
    }
    $review = CommentReview::where([['comment_id', $comment_id], ['reviewer_id', $reviewer_id]])->first();
    if ($review !== null) {
      if ($review->review === $request->review) {
        $review->review = null;
        $result = $review->delete();
      } else {
        $review->review = $request->review;
        $result = $review->save();
      }
    } else {
      $review = new CommentReview;
      $review->reviewer_id = $reviewer_id;
      $review->comment_id = $comment_id;
      $review->review = $request->review;
      $result = $review->save();
    }
    if ($result) {
      $comment->like_count = CommentReview::where([['comment_id', $comment_id], ['review', 1]])->count();
      $comment->dislike_count = CommentReview::where([['comment_id', $comment_id], ['review', 0]])->count();
      $comment->save();
      if ($request->review === 1 && $review->review !== null && $comment->commenter_id !== $reviewer_id) {
        $text = "👍 Someone liked your commented: &quot;".$comment->text."&quot;";
        $notification = new Notification;
        $notification->from = $reviewer_id;
        $notification->for = $comment->commenter_id;
        $notification->type = "like";
        $notification->text = $text;
        $notification->url = URL::signedRoute('comments.highlighted', ['video_id' => $comment->video_id, 'comment_id' => $comment_id]);
        $notification->logo_url = "TODO";
        $notification->save();
      }
      return ['success' => true];
    }
    return response()->json(['success' => false], 451);
  }

  // Post reply on a comment
  public function postReply(Request $request, $comment_id) {
    $request->validate([
      'text' => 'bail|required|string|max:300'
    ]);

    $replier_id = $request->user()->id;
    $comment = Comment::find($comment_id);

    if ($comment->video->visibility === "private" && $comment->video->channel_id !== $commenter_id) {
      return accessDenied();
    }
    $reply = new Reply;
    $reply->replier_id = $replier_id;
    $reply->text = $request->text;
    $reply->comment_id = $comment_id;
    $result = $reply->save();
    if ($result) {
      $comment->increment('reply_count', 1);
      if ($comment->commenter_id !== $replier_id) {
        $channel = Channel::find($replier_id, ['name', 'logo_url']);
        $text = $channel->name." replied: &quot;".$reply->text."&quot;";
        $notification = new Notification;
        $notification->from = $replier_id;
        $notification->for = $comment->commenter_id;
        $notification->type = "reply";
        $notification->text = $text;
        $notification->url = URL::signedRoute('replies.highlighted', ['comment_id' => $comment_id, 'reply_id' => $reply->id]);
        $notification->logo_url = $channel->logo_url;
        $notification->thumbnail_url = $comment->video->thumbnail_url;
        $notification->save();
      }
      return ['success' => true];
    }
    return response()->json(['success' => false], 451);
  }

  // Get all replies of a specific comment
  public function getReplies(Request $request, $comment_id) {
    $comment = Comment::find($comment_id);

    if (!$request->user()->is_admin && ($comment->video->visibility === "private" && $comment->video->channel_id !== $request->user()->id)) {
      return accessDenied();
    }
    $replies = $comment->replies;
    foreach ($replies as $reply) {
      $reply->review = ReplyReview::where([['reply_id', $reply->id], ['reviewer_id', $request->user()->id]])->value('review');
      $reply->author = ($reply->replier_id === $comment->video->channel_id);
    }
    return $replies;
  }

  // Get all Replies of a comment with 1 highlighted one. for notification view
  public function getRepliesWithHighlighted(Request $request, $comment_id, $reply_id) {
    $comment = Comment::find($comment_id);
    $replies = $comment->replies;
    foreach ($replies as $reply) {
      $reply->review = ReplyReview::where([['reply_id', $reply->id], ['reviewer_id', $request->user()->id]])->value('review');
      $reply->highlight = ($reply->id == $reply_id);
      $reply->author = ($reply->replier_id === $comment->video->channel_id);
    }
    return [
      'heading' => "Replies on &quot;".$comment->text."&quot;",
      'link' => $comment->video->link,
      'thumbnail_url' => $comment->video->thumbnail_url,
      'replies' => $replies
    ];
  }

  // Update a reply
  public function updateReply(Request $request, $reply_id) {
    $request->validate([
      'text' => 'bail|required|string|max:300'
    ]);
    $reply = Reply::find($reply_id);

    if ($reply->reviewer_id !== $request->user()->id) {
      return accessDenied();
    }
    $reply->text = $request->text;
    $result = $comment->save();
    if ($result) {
      return ['success' => true];
    }
    return response()->json(['success' => false], 451);
  }

  // Delete a reply
  public function removeReply(Request $request, $reply_id) {
    $reply = Reply::find($reply_id);

    $user_id = $request->user()->id;
    if ($request->user()->is_admin || $reply->replier_id === $user_id || $reply->video->channel_id === $user_id) {
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
    return accessDenied();
  }

  // Like or Dislike a comment
  public function postReplyReview(Request $request, $reply_id) {
    $request->validate([
      'review' => 'bail|required|in:0,1'
    ]);
    $reply = Reply::find($reply_id);

    $reviewer_id = $request->user()->id;
    if ($reply->video->visibility === "private" && $reply->video->channel_id !== $reviewer_id) {
      return accessDenied();
    }
    $review = ReplyReview::where('reply_id', $reply_id)->where('reviewer_id', $reviewer_id)->first();
    if ($review !== null) {
      if ($review->review === $request->review) {
        $review->review = null;
        $result = $review->delete();
      } else {
        $review->review = $request->review;
        $result = $review->save();
      }
    } else {
      $review = new ReplyReview;
      $review->reviewer_id = $reviewer_id;
      $review->reply_id = $reply_id;
      $review->review = $request->review;
      $result = $review->save();
    }
    if ($result) {
      $reply->like_count = ReplyReview::where([['reply_id', $reply_id], ['review', 1]])->count();
      $reply->dislike_count = ReplyReview::where([['reply_id', $reply_id], ['review', 0]])->count();
      $reply->save();
      if ($request->review === 1 && $review->review !== null && $reply->replier_id !== $reviewer_id) {
        $text = "👍 Someone liked your commented: &quot;".$reply->text."&quot;";
        $notification = new Notification;
        $notification->from = $reviewer_id;
        $notification->for = $reply->replier_id;
        $notification->type = "like";
        $notification->text = $text;
        $notification->url = URL::signedRoute('replies.highlighted', ['comment_id' => $reply->comment_id, 'reply_id' => $reply->id]);
        $notification->logo_url = "TODO";
        $notification->save();
      }
      return ['success' => true];
    }
    return response()->json(['success' => false], 451);
  }

  // Give heart on a comment or reply
  public function giveHeart($type, $id) {
    $user_id = auth()->user()->id;
    $comment = ($type == "comment")
    ?Comment::find($id)
    :Reply::find($id);
    if ($comment->video->channel_id !== $user_id) {
      return accessDenied();
    }
    $comment->heart = intval(!$comment->heart);
    $result = $comment->save();
    if ($result) {
      if ($comment->heart)
        $name = Channel::where('id', $user_id)->value('name');
      $text = "Your comment got a ❤️ from $name!";
      $notification = new Notification;
      $notification->from = $user_id;
      $notification->for = ($type == "comment")
      ?$comment->commenter_id
      :$comment->replier_id;
      $notification->url = ($type == "comment")
      ?URL::signedRoute('comments.highlighted', ['video_id' => $comment->video_id, 'comment_id' => $id])
      :URL::signedRoute('replies.highlighted', ['comment_id' => $comment_id, 'reply_id' => $id]);
      $notification->type = "heart";
      $notification->text = $text;
      $notification->logo_url = $comment->video->channel->logo_url;
      $notification->save();
      return ['success' => true];
    }
    return response()->json(['success' => false], 451);
  }

  // Get all notifications of a user
  public function getNotifications(Request $request) {
    $id = $request->user()->id;
    $subscriptions = Subscriber::where('subscriber_id', $id)->get('channel_id');
    $hidden_notifications = Hidden::where('user_id', $id)->get('notification_id');
    $subscriptions_id = array();
    $hidden_notifications_id = array();
    foreach ($hidden_notifications as $notification) {
      array_push($hidden_notifications_id, $notification->notification_id);
    }
    foreach ($subscriptions as $subscription) {
      array_push($subscriptions_id, $subscription->channel_id);
    }

    $notifications = Notification::where(function ($query) use ($subscriptions_id) {
      $query->where('type', 'video')->whereIn('from', $subscriptions_id);
    })->orWhere(function ($query) use ($id) {
      $query->whereIn('type', ['comment', 'reply', 'heart', 'subscribe', 'like'])->where('for', $id);
    })->whereNotIn('id', $hidden_notifications_id)->latest()->limit(40)->get();
    return $notifications;
  }

  // Hide a Notification
  public function hideNotification(Request $request, $notification_id) {
    $hidden = new Hidden;
    $hidden->user_id = $request->user()->id;
    $hidden->notification_id = $notification_id;
    $result = $hidden->save();
    if ($result) {
      return ['success' => true,
        'message' => 'Notification successfully hided!'];
    }
    return response()->json([
      'success' => false,
      'message' => 'Failed to hide notification!'
    ], 451);
  }

  // Delete history
  public function removeHistory(Request $request, $history_id) {
    $history = History::find($history_id);
    $user_id = $request->user()->id;
    if ($history->user_id === $user_id) {
      $result = $history->delete();
      if ($result) {
        return ['success' => true,
          'message' => 'History successfully deleted!'];
      }
      return response()->json([
        'success' => false,
        'message' => 'Failed to delete history!'
      ], 451);
    }
    return accessDenied();
  }

  // Get a users saved, created and liked videos Playlist
  public function getPlaylists(Request $request) {
    $saved_playlists = SavedPlaylist::where('user_id', $request->user()->id)->select('playlist_id')->get();
    $saved_playlists_id = array();
    foreach ($saved_playlists as $saved_playlist){
      array_push($saved_playlists_id, $saved_playlist->playlist_id);
    }
    $playlists = Playlist::where('user_id', $request->user()->id)->orWhere(function ($query) use ($saved_playlists_id){
      $query->whereIn('id', $saved_playlists_id);
    })->orderByDesc('updated_at')->get();
    return $playlists;
  }

  // Create a playlist
  public function createPlaylist(Request $request) {
    $request->validate([
      'name' => 'bail|required|string|between:1,30',
      'description' => 'bail|string|max:300',
      'visibility' => 'bail|required|in:public,private',
    ]);
    $playlist = new Playlist;
    $playlist->user_id = $request->user()->id;
    $playlist->name = $request->name;
    $playlist->description = $request->description;
    $playlist->visibility = $request->visibility;
    $playlist->link = URL::signedRoute('playlist.videos', ['id' => $playlist->getNextId()]);
    if ($playlist->save()) {
      return ['success' => true,
        'message' => 'Playlist successfully created!'];
    }
    return response()->json([
      'success' => false,
      'message' => 'Failed to create playlist!'
    ], 451);
  }

  // Update a playlist
  public function updatePlaylist(Request $request, $id) {
    $request->validate([
      'name' => 'bail|required|string|between:1,30',
      'description' => 'bail|string|max:300',
      'visibility' => 'bail|required|in:public,private',
    ]);
    $playlist = Playlist::find($id);
    if (!$request->user()->is_admin && $playlist->user_id !== $request->user()->id) {
      return accessDenied();
    }
    $playlist->name = $request->name;
    $playlist->description = $request->description;
    $playlist->visibility = $request->visibility;
    if ($playlist->save()) {
      return ['success' => true,
        'message' => 'Playlist successfully updated!'];
    }
    return response()->json([
      'success' => false,
      'message' => 'Failed to update playlist!'
    ], 451);
  }

  // Remove a playlist
  public function removePlaylist($id) {
    $playlist = Playlist::find($id);
    if (!auth()->user()->is_admin && $playlist->user_id !== auth()->user()->id) {
      return accessDenied();
    }
    if ($playlist->delete()) {
      return [
        'success' => true,
        'message' => 'Playlist successfully deleted!'
      ];
    }
    return response()->json([
      'success' => false,
      'message' => 'Failed to delete playlist!'
    ], 451);
  }

  // Save others public playlist
  public function savePlaylist($id) {
    if (SavedPlaylist::where('user_id', auth()->user()->id)->where('playlist_id', $id)->exists()) {
      return response()->json([
        'success' => false,
        'message' => 'Playlist already exist in library!'
      ], 451);
    }
    $playlist = Playlist::find($id);
    if ($playlist->visibility === "private" && !auth()->user()->is_admin) {
      return accessDenied();
    }
    if ($playlist->user_id === auth()->user()->id) {
      return response()->json([
        'success' => false,
        'message' => 'Can\'t save your own playlist!'
      ], 406);
    }
    $saved_playlist = new SavedPlaylist;
    $saved_playlist->user_id = auth()->user()->id;
    $saved_playlist->playlist_id = $id;
    if ($saved_playlist->save()) {
      return [
        'success' => true,
        'message' => 'Playlist saved to library!'
      ];
    }
    return response()->json([
      'success' => false,
      'message' => 'Failed saving playlist to library!'
    ], 451);
  }

  // Remove saved playlist
  public function removeSavedPlaylist($id) {
    $result = SavedPlaylist::where('user_id', auth()->user()->id)->where('playlist_id', $id)->delete();
    if ($result) {
      return [
        'success' => true,
        'message' => 'Playlist removed from library!'
      ];
    }
    return response()->json([
      'success' => false,
      'message' => 'Failed removing playlist from library!'
    ], 451);
  }

  // Add video to playlist
  public function addVideoToPlaylist($playlist_id, $video_id) {
    if (!Video::find($video_id)) {
      return response()->json([
        'success' => false,
        'message' => 'Video not found!'
      ], 404);
    }
    $playlist = Playlist::find($playlist_id);
    if (!auth()->user()->is_admin && $playlist->user_id !== auth()->user()->id) {
      return accessDenied();
    }
    $playlist_video_exists = PlaylistVideo::where('playlist_id', $playlist_id)->where('video_id', $video_id)->exists();
    if ($playlist_video_exists) {
      return response()->json([
        'success' => false,
        'message' => 'Video already exist in the playlist!'
      ], 451);
    }
    $playlist_video = new PlaylistVideo;
    $playlist_video->playlist_id = $playlist_id;
    $playlist_video->video_id = $video_id;
    if ($playlist_video->save()) {
      $playlist->increment('total_videos', 1);
      return ['success' => true,
        'message' => 'Video added to &quot;'.$playlist->name.'&quot;!'];
    }
    return response()->json([
      'success' => false,
      'message' => 'Failed to add video!'
    ], 451);
  }

  // Remove video from a playlist
  public function removeVideoFromPlaylist($playlist_id, $video_id) {
    $playlist = Playlist::find($playlist_id);
    if (!auth()->user()->is_admin && $playlist->user_id !== auth()->user()->id) {
      return accessDenied();
    }
    if (PlaylistVideo::where('playlist_id', $playlist_id)->where('video_id', $video_id)->delete()) {
      $playlist->decrement('total_videos', 1);
      return [
        'success' => true,
        'message' => 'Video removed from &quot;'.$playlist->name.'&quot;!'
      ];
    }
    return response()->json([
      'success' => false,
      'message' => 'Failed to remove video!'
    ], 451);
  }

  // Get all videos of a playlist
  public function getPlaylistVideos($id) {
    $playlist = Playlist::find($id);
    if ($playlist->visibility === "private" && !auth()->user()->is_admin && $playlist->user_id !== auth()->user()->id) {
      return accessDenied();
    }
    $playlist_videos = array();
    foreach ($playlist->videos as $playlist_video) {
      $video = Video::where('id', $playlist_video->video_id)->channel(['name'])->first();
      array_push($playlist_videos, $video);
    }
    return $playlist_videos;
  }

  // Save a uploaded file to server storage
  protected function upload($path, $file) {
    $file_extention = $file->extension();
    $file_name = time().$file->getClientOriginalName();
    return $file->storeAs("uploads/$path", $file_name, 'public');
  }

  // Clear Unimportant files from server storage
  protected function clear($path) {
    return unlink(storage_path("app/public/$path"));
  }

}