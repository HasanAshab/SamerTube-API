<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use App\Rules\CSVRule;
use App\Models\User;
use App\Models\Video;
use App\Models\Post;
use App\Models\Poll;
use App\Models\Vote;
use App\Models\View;
use App\Models\Category;
use App\Models\History;
use App\Models\Review;
use App\Models\Channel;
use App\Models\Comment;
use App\Models\Reply;
use App\Models\Subscriber;
use App\Models\Notification;
use App\Models\Hidden;
use App\Models\Playlist;
use App\Models\WatchLater;
use App\Models\PlaylistVideo;
use App\Models\SavedPlaylist;
use App\Models\Report;
use App\Events\Watched;
use App\Jobs\PublishVideo;
use App\Jobs\PublishPost;
use App\Mail\VideoUploadedMail;
use App\Mail\CommentedMail;
use App\Mail\RepliedMail;
use App\Mail\LikedMail;
use App\Mail\GotHeartMail;
use Carbon\Carbon;
use Mail;
use DB;

class videoApi extends Controller
{
  // Get all public videos
  public function explore(Request $request) {
    $video_query = Video::with(['channel' => function ($query){
      return $query->select('id', 'name', 'logo_url');
    }]);
    if (!(auth()->check() || auth()->user()->is_admin)) {
      $video_query->where('visibility', 'public');
    }
    if (isset($request->limit)) {
      $offset = isset($request->offset)
      ?$request->offset
      :0;
      $video_query->offset($offset)->limit($request->limit);
    }
    $videos = $video_query->rank()->get();
    return $videos;
  }

  // Save a new video
  public function store(Request $request) {
    $request->validate([
      'title' => 'bail|required|string|between:1,72',
      'description' => 'bail|required|string|max:300',
      'visibility' => 'bail|required|in:public,private,scheduled',
      'publish_at' => 'date_format:Y-m-d H:i:s|after_or_equal:'.date(DATE_ATOM),
      'category_id' => 'bail|required|exists:categories,id',
      'video' => 'bail|required|mimes:mp4',
      'thumbnail' => 'bail|required|image'
    ]);
    $video = new Video;
    $video->title = $request->title;
    $video->description = $request->description;
    $video->category_id = $request->category_id;
    $video->visibility = $request->visibility;
    $video->link = URL::signedRoute('video.watch', ['id' => $video->getNextId()]);
    require_once(storage_path('getID3/getid3/getid3.php'));
    $getID3 = new \getID3;
    $video->duration = $getID3->analyze($request->file('video'))['playtime_seconds'];
    $urls = $video->attachFiles([
      'video' => $request->file('video'),
      'thumbnail' => $request->file('thumbnail')
    ]);
    $video->video_url = $urls->video;
    $video->thumbnail_url = $urls->thumbnail;
    $result = $video->save();
    if ($result) {
      if ($video->visibility === 'public') {
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
        $subscribers_id = Subscriber::where('channel_id', $video->channel_id)->pluck('subscriber_id');
        $subscribers_email = User::whereIn('id', $subscribers_id)->pluck('email');
        $data = [
          'subject' => str_replace('&quot;', '"', $text),
          'channel_name' => $video->channel->name,
          'channel_logo_url' => $video->channel->logo_url,
          'title' => $video->title,
          'description' => substr($video->description, 0, 50).'...',
          'thumbnail_url' => $video->thumbnail_url,
          'link' => $video->link,
        ];
        $this->notify($subscribers_email, $data, $notification->type);
      } else if ($video->visibility === 'scheduled') {
        PublishVideo::dispatch($video, true)->delay($request->publish_at);
      }
      return ['success' => $result,
        'message' => 'Video successfully uploaded!'];
    }
    return response()->json(['success' => $result,
      'message' => 'Failed to upload video!'], 451);

  }

  // update own video
  public function update(Request $request, $id) {
    $request->validate([
      'title' => 'bail|required|string|between:1,72',
      'description' => 'bail|required|string|max:300',
      'visibility' => 'bail|required|in:public,private,scheduled',
      'publish_at' => 'date_format:Y-m-d H:i:s|after_or_equal:'.date(DATE_ATOM),
      'category_id' => 'bail|required|between:1,9',
      'allow_comments' => 'bail|required|in:0,1',
      'tags' => ['bail', new CSVRule()],
      'thumbnail' => 'image'
    ]);
    $video = Video::find($id);
    if (!$request->user()->can('update', [Video::class, $video])) {
      abort(405);
    }
    $video->title = $request->title;
    $video->description = $request->description;
    $video->visibility = $request->visibility;
    $video->category_id = $request->category_id;
    $video->allow_comments = $request->allow_comments;
    $video->setTags(explode(',', $request->tags));
    if ($request->file('thumbnail') !== null) {
      $video->removeFiles('thumbnail');
      $video->thumbnail_url = $video->attachFile('thumbnail', $request->file('thumbnail'), true);
    }
    $result = $video->save();
    if ($result) {
      if ($video->visibility === 'scheduled') {
        PublishVideo::dispatch($video, false)->delay($request->publish_at);
      }
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
    $video = Video::where('id', $id)->channel()->select('videos.*', 'channels.name', 'channels.logo_url', 'channels.total_subscribers')->first();
    if (!$request->user()->can('watch', [Video::class, $video]) || (!auth()->check() && $video->visibility !== 'public')) {
      abort(405);
    }
    if(auth()->check()){
      event(new Watched(auth()->user(), $id));
    }
    $video->author = auth()->check() && ($video->channel_id === $request->user()->id);
    $video->subscribed = auth()->check() && Subscriber::where('subscriber_id', $request->user()->id)->where('channel_id', $video->channel_id)->exists();
    return $video;
  }

  // Set watch time of a view
  public function setViewWatchTime($id, $time) {
    $view = View::where('user_id', auth()->user()->id)->where('video_id', $id)->first();
    if ($view) {
      $view->view_duration += $time;
      $result = $view->save();
    } else {
      $view = new View;
      $view->video_id = $id;
      $view->view_duration = $time;
      $result = $view->save() && Video::find($id)->increment('view_count', 1);
    }
    return $result
    ?response()->noContent()
    :response()->json(['success' => false], 451);
  }
  // Delete own video
  public function destroy($id) {
    $video = Video::find($id);
    if (!$request->user()->can('delete', [Video::class, $video])) {
      abort(405);
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

  //Get watch history
  public function watchHistory(Request $request) {
    $id = $request->user()->id;
    $date_query = History::where('user_id', $id)->whereNotNull('video_id')->select(DB::raw('DATE(created_at) as date'))->distinct('date')->latest();
    if (isset($request->limit)) {
      $offset = isset($request->offset)
      ?$request->offset
      :0;
      $date_query->offset($offset)->limit($request->limit);
    }
    $dates = $date_query->pluck('date');
    $histories = collect();
    foreach ($dates as $date) {
      $videos = History::whereDate('histories.created_at', $date)->whereNotNull('video_id')->where('user_id', $id)->join('videos', 'videos.id', '=', 'histories.history')->join('channels', 'channels.id', 'videos.channel_id')->select('history', 'channels.name', 'videos.title', DB::raw('TIME_FORMAT(SEC_TO_TIME(videos.duration), "%i:%s") AS duration'), 'videos.thumbnail_url')->get();
      $histories->push(['date' => $date, 'videos' => $videos]);
    }
    return $histories;
  }

  // Get liked videos
  public function getLikedVideos(Request $request) {
    return Video::liked($request->limit, $request->offset);
  }

  // Get watch later videos
  public function getWatchLaterVideos(Request $request) {
    $watch_later_query = WatchLater::where('user_id', auth()->user()->id);
    if (isset($request->limit)) {
      $offset = isset($request->offset)
      ?$request->offset
      :0;
      $watch_later_query->offset($offset)->limit($request->limit);
    }
    $watch_later_videos_id = $watch_later_query->pluck('video_id');
    $videos = Video::with(['channel' => function ($query){
      return $query->select('id', 'name');
    }])->whereIn('id', $watch_later_videos_id)->get();
    return $videos;
  }

  // Like and dislike on a video
  public function review(Request $request, $video_id) {
    $request->validate([
      'review' => 'bail|required|in:0,1'
    ]);
    $video = Video::find($video_id);

    if (!$request->user()->can('review', [Video::class, $video])) {
      abort(405);
    }
    $reviewed = $video->reviewed();
    if ($reviewed === $request->review) {
      $video->unreview();
      return response()->noContent();
    }

    $result = $video->review($request->review);
    if ($result) {
      return response()->noContent();
    }
    return response()->json(['success' => false], 451);
  }

  // Get what is the review of user
  public function getReview(Request $request, $video_id) {
    $review_code = Video::find($video_id)->reviewed();
    return ['review' => $review_code];
  }

  // Create comment on a video
  public function createComment(Request $request, $video_id) {
    $request->validate([
      'text' => 'bail|required|string|max:300'
    ]);
    $commenter_id = $request->user()->id;
    $video = Video::find($video_id);
    if (!$request->user()->can('createComment', [Video::class, $video])) {
      abort(405);
    }
    $comment = $video->comment($request->text);
    if ($comment) {
      if ($video->channel_id !== $commenter_id) {
        $text = $request->user()->channel->name." commented: &quot;".$comment->text."&quot;";
        $notification = new Notification;
        $notification->from = $commenter_id;
        $notification->for = $video->channel_id;
        $notification->type = "comment";
        $notification->url = URL::signedRoute('comments.highlighted', ['video_id' => $video->id, 'comment_id' => $comment->id]);
        $notification->text = $text;
        $notification->logo_url = $request->user()->channel->logo_url;
        $notification->thumbnail_url = $video->thumbnail_url;
        $notification->save();
        $data = [
          'subject' => str_replace('&quot;', '"', $text),
          'commenter_name' => $request->user()->channel->name,
          'commenter_logo_url' => $request->user()->channel->logo_url,
          'text' => $comment->text,
          'link' => $notification->url
        ];
        $uploader_email = User::find($video->channel_id)->email;
        $this->notify($uploader_email, $data, $notification->type);
      }
      return response()->noContent();
    }
    return response()->json(['success' => false], 451);
  }

  // Get all comments of a specific video
  public function getComments(Request $request, $video_id) {
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
      $comment->author = ($comment->commenter_id === $video->channel_id);
    }
    return $comments;
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

  // Update comment
  public function updateComment(Request $request, $comment_id) {
    $request->validate([
      'text' => 'bail|required|string|max:300'
    ]);
    $comment = Comment::find($comment_id);
    if (!$request->user()->can('updateComment', [Video::class, $comment])) {
      abort(405);
    }
    $comment->text = $request->text;
    $result = $comment->save();
    if ($result) {
      return response()->noContent();
    }
    return response()->json(['success' => false], 451);
  }

  // Delete a comment
  public function removeComment(Request $request, $comment_id) {
    $comment = Comment::find($comment_id);
    $user_id = $request->user()->id;
    if ($request->user()->can('deleteComment', [Video::class, $comment])) {
      $result = $comment->delete();
      if ($result) {
        $comment->commentable->decrement('comment_count', 1);
        return ['success' => true,
          'message' => 'Comment successfully deleted!'];
      }
      return response()->json([
        'success' => false,
        'message' => 'Failed to delete comment!'
      ], 451);
    }
    abort(405);
  }

  // Like or Dislike a comment
  public function reviewComment(Request $request, $comment_id) {
    $request->validate([
      'review' => 'bail|required|in:0,1'
    ]);
    $comment = Comment::find($comment_id);

    $reviewer_id = $request->user()->id;
    if ($comment->video->visibility !== "public" && $comment->video->channel_id !== $reviewer_id) {
      abort(405);
    }

    if ($comment->reviewed() === $request->review) {
      $comment->unreview();
      return response()->noContent();
    }

    $result = $comment->review($request->review);
    if ($result) {
      if ($comment->reviewed() === 1 && $comment->commenter_id !== $reviewer_id) {
        $text = "👍 Someone liked your commented: &quot;".$comment->text."&quot;";
        $notification = new Notification;
        $notification->from = $reviewer_id;
        $notification->for = $comment->commenter_id;
        $notification->type = "like";
        $notification->text = $text;
        $notification->url = URL::signedRoute('comments.highlighted', ['video_id' => $comment->video_id, 'comment_id' => $comment_id]);
        $notification->logo_url = URL::signedRoute('file.serve', ['type' => 'company-logo']);
        $notification->save();
        $commenter = User::find($comment->commenter_id);
        $data = [
          'subject' => str_replace('&quot;', '"', $text),
          'name' => $commenter->channel->name,
          'logo_url' => $commenter->channel->logo_url,
          'text' => $comment->text,
          'link' => $notification->url,
        ];
        $this->notify($commenter->email, $data, $notification->type);
      }
      return response()->noContent();
    }
    return response()->json(['success' => false], 451);
  }

  // Create reply on a comment
  public function createReply(Request $request, $comment_id) {
    $request->validate([
      'text' => 'bail|required|string|max:300'
    ]);

    $replier_id = $request->user()->id;
    $comment = Comment::find($comment_id);

    if (!$request->user()->can('create', [Reply::class, $comment])) {
      abort(405);
    }
    $reply = new Reply;
    $reply->text = $request->text;
    $reply->comment_id = $comment_id;
    $result = $reply->save();
    if ($result) {
      $comment->increment('reply_count', 1);
      if ($comment->commenter_id !== $replier_id) {
        $text = $request->user()->channel->name." replied: &quot;".$reply->text."&quot;";
        $notification = new Notification;
        $notification->from = $replier_id;
        $notification->for = $comment->commenter_id;
        $notification->type = "reply";
        $notification->text = $text;
        $notification->url = URL::signedRoute('replies.highlighted', ['comment_id' => $comment_id, 'reply_id' => $reply->id]);
        $notification->logo_url = $request->user()->channel->logo_url;
        $notification->thumbnail_url = $comment->video->thumbnail_url;
        $notification->save();
        $data = [
          'subject' => str_replace('&quot;', '"', $text),
          'replier_name' => $request->user()->channel->name,
          'replier_logo_url' => $request->user()->channel->logo_url,
          'text' => $reply->text,
          'link' => $notification->url,
        ];
        $uploader_email = User::find($video->channel_id)->email;
        $this->notify($uploader_email, $data, $notification->type);
      }
      return response()->noContent();
    }
    return response()->json(['success' => false], 451);
  }

  // Get all replies of a specific comment
  public function getReplies(Request $request, $comment_id) {
    $comment = Comment::find($comment_id);

    if (!$request->user()->can('read', [Reply::class, $comment])) {
      abort(405);
    }
    $reply_query = $comment->replies();
    if (isset($request->limit)) {
      $offset = isset($request->offset)
      ?$request->offset
      :0;
      $reply_query->offset($offset)->limit($request->limit);
    }
    $replies = $reply_query->get();
    foreach ($replies as $reply) {
      $reply->review = $reply->reviewed();
      $reply->author = ($reply->replier_id === $comment->commentable->channel_id);
    }
    return $replies;
  }

  // Get all Replies of a comment with 1 highlighted one. for notification view
  public function getRepliesWithHighlighted(Request $request, $comment_id, $reply_id) {
    $comment = Comment::find($comment_id);
    $reply_query = $comment->replies();
    if (isset($request->limit)) {
      $offset = isset($request->offset)
      ?$request->offset
      :0;
      $reply_query->offset($offset)->limit($request->limit);
    }
    $replies = $reply_query->get();
    foreach ($replies as $reply) {
      $reply->review = $reply->reviewed();
      $reply->highlight = ($reply->id == $reply_id);
      $reply->author = ($reply->replier_id === $comment->commentable->channel_id);
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
  public function removeReply(Request $request, $reply_id) {
    $reply = Reply::find($reply_id);

    $user_id = $request->user()->id;
    if ($request->user()->can('delete', [Reply::class, $reply])) {
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

  // Like or Dislike a Reply
  public function reviewReply(Request $request, $reply_id) {
    $request->validate([
      'review' => 'bail|required|in:0,1'
    ]);
    $reply = Reply::find($reply_id);

    $reviewer_id = $request->user()->id;
    if ($reply->video->visibility !== "public" && $reply->video->channel_id !== $reviewer_id) {
      abort(405);
    }
    if ($reply->reviewed() === $reply->review) {
      $reply->unreview();
      return response()->noContent();
    }
    $result = $reply->review($request->review);

    if ($result) {
      if ($reply->reviewed() === 1 && $reply->replier_id !== $reviewer_id) {
        $text = "👍 Someone liked your commented: &quot;".$reply->text."&quot;";
        $notification = new Notification;
        $notification->from = $reviewer_id;
        $notification->for = $reply->replier_id;
        $notification->type = "like";
        $notification->text = $text;
        $notification->url = URL::signedRoute('replies.highlighted', ['comment_id' => $reply->comment_id, 'reply_id' => $reply->id]);
        $notification->logo_url = URL::signedRoute('file.serve', ['type' => 'company-logo']);
        $notification->save();
        $replier = User::find($reply->commenter_id);
        $data = [
          'subject' => str_replace('&quot;', '"', $text),
          'name' => $replier->channel->name,
          'logo_url' => $replier->channel->logo_url,
          'text' => $reply->text,
          'link' => $notification->url,
        ];
        $this->notify($replier->email, $data, $notification->type);

      }
      return response()->noContent();
    }
    return response()->json(['success' => false], 451);
  }

  // Give heart on a comment or reply
  public function giveHeart($type, $id) {
    $user_id = auth()->user()->id;
    $comment = ($type == "comment")
    ?Comment::find($id)
    :Reply::find($id);
    if ($comment->commentable->channel_id !== $user_id) {
      abort(405);
    }
    $comment->heart = (int)!$comment->heart;
    $result = $comment->save();
    if ($result) {
      if ($comment->heart) {
        $channel = auth()->user()->channel;
        $text = "Your comment got a ❤️ from ".$channel->name."!";
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
        $notification->logo_url = $comment->commentable->channel->logo_url;
        $notification->save();
        $commenter = User::find($notification->for);
        $data = [
          'subject' => str_replace('&quot;',
            '"',
            $text),
          'name' => $channel->name,
          'logo_url' => $channel->logo_url,
          'text' => $comment->text,
          'link' => $notification->url,
        ];
        $this->notify($commenter->email,
          $data,
          $notification->type);
      }
      return response()->noContent();
    }
    return response()->json(['success' => false],
      451);
  }

  // Get all notifications of a user
  public function getNotifications(Request $request) {
    $id = $request->user()->id;
    $subscriptions_id = Subscriber::where('subscriber_id',
      $id)->pluck('channel_id');
    $hidden_notifications_id = Hidden::where('user_id',
      $id)->pluck('notification_id');
    $notification_query = Notification::where(function ($query) use ($subscriptions_id) {
      $query->where('type', 'video')->whereIn('from', $subscriptions_id);
    })->orWhere(function ($query) use ($id) {
      $query->whereIn('type', ['comment', 'reply', 'heart', 'subscribe', 'like'])->where('for', $id);
    })->whereNotIn('id',
      $hidden_notifications_id)->latest();
    if (isset($request->limit)) {
      $offset = isset($request->offset)
      ?$request->offset
      :0;
      $notification_query->offset($offset)->limit($request->limit);
    }
    $notifications = $notification_query->get();
    return $notifications;
  }

  // Hide a Notification
  public function hideNotification(Request $request, $notification_id) {
    $hidden = new Hidden;
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
    abort(405);
  }

  // Get a users saved, created and liked videos Playlist
  public function getPlaylists() {
    $saved_playlists_id = SavedPlaylist::where('user_id', auth()->user()->id)->pluck('playlist_id');
    $liked_videos_playlist = [
      'name' => 'Liked videos',
      'total_videos' => Review::where('reviewer_id', auth()->user()->id)->where('review', 1)->count(),
      'link' => route('videos.liked'),
      'thumbnail_url' => URL::signedRoute('file.serve', ['type' => 'liked-videos'])
    ];
    $watch_later = [
      'name' => 'Watch later',
      'total_videos' => WatchLater::where('user_id', auth()->user()->id)->count(),
      'link' => route('videos.watchLater'),
      'thumbnail_url' => URL::signedRoute('file.serve', ['type' => 'watch-later'])
    ];
    $playlists = Playlist::where('user_id', auth()->user()->id)->orWhere(function ($query) use ($saved_playlists_id) {
      $query->whereIn('id', $saved_playlists_id);
    })->orderByDesc('updated_at')->get();

    $playlists->prepend($liked_videos_playlist);
    $playlists->prepend($watch_later);
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
    if ($playlist->user_id !== $request->user()->id) {
      abort(405);
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
      abort(405);
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
      abort(405);
    }
    if ($playlist->user_id === auth()->user()->id) {
      return response()->json([
        'success' => false,
        'message' => 'Can\'t save your own playlist!'
      ], 406);
    }
    $saved_playlist = new SavedPlaylist;
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
    $playlist = Playlist::find($playlist_id);
    if ($playlist->user_id !== auth()->user()->id) {
      abort(405);
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
    if ($playlist->user_id !== auth()->user()->id) {
      abort(405);
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

  // Add video to Watch Later
  public function addVideoToWatchLater($video_id) {
    if (!Video::find($video_id)) {
      return response()->json([
        'success' => false,
        'message' => 'Video not found!'
      ], 404);
    }
    $watch_later_video_exists = WatchLater::where('user_id', auth()->user()->id)->where('video_id', $video_id)->exists();
    if ($watch_later_video_exists) {
      return response()->json([
        'success' => false,
        'message' => 'Video already exist in watch later!'
      ], 451);
    }
    $watch_later = new WatchLater;
    $watch_later->video_id = $video_id;
    if ($watch_later->save()) {
      return [
        'success' => true,
        'message' => 'Video added to watch later!'
      ];
    }
    return response()->json([
      'success' => false,
      'message' => 'Failed to add video!'
    ], 451);
  }

  // Remove video from watch later
  public function removeVideoFromWatchLater($video_id) {
    if (!Video::find($video_id)) {
      return response()->json([
        'success' => false,
        'message' => 'Video not found!'
      ], 404);
    }
    $result = WatchLater::where('user_id', auth()->user()->id)->where('video_id', $video_id)->delete();
    if ($result) {
      return [
        'success' => true,
        'message' => 'Video removed from watch later!'
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
    if ($playlist->visibility !== "public" && !auth()->user()->is_admin && $playlist->user_id !== auth()->user()->id) {
      abort(405);
    }
    $videos = collect();
    $playlist_video_query = $playlist->videos();
    if (isset($request->limit)) {
      $offset = isset($request->offset)?$request->offset:0;
      $playlist_video_query->offset($offset)->limit($request->limit);
    }
    $videos = $playlist_video_query->with(['channel' => function ($query){
      return $query->select('id', 'name');
    }])->where('visibility', 'public')->get();
    return $videos;
  }

  // Report a video
  public function report(Request $request, $id) {
    $request->validate([
      'reason' => 'required|string|between:10,100'
    ]);
    $video = Video::find($id);
    if (!$request->user()->can('watch', [Video::class, $video])) {
      abort(405);
    }
    
    if ($video->report($request->reason)) {
      return [
        'success' => true,
        'message' => 'Thanks for reporting!'
      ];
    }
    return response()->json([
      'success' => false,
      'message' => 'Failed to report!'
    ], 451);
  }

  // Create a Community Post
  public function createPost(Request $request) {
    $request->validate([
      'content' => 'bail|required|string',
      'visibility' => 'bail|required|in:public,scheduled',
      'publish_at' => 'date_format:Y-m-d H:i:s|after_or_equal:'.date(DATE_ATOM),
      'type' => 'bail|required|in:text,text_poll,image_poll,shared',
      'shared_id' => 'bail|exists:posts,id',
      'polls' => 'array|min:2|max:5',
      'images' => 'array|min:1|max:5',
      'poll_images' => 'array|min:2|max:5',
    ]);
    if (!$request->user()->can('create', [Post::class])) {
      abort(405);
    }
    if ($request->type === 'text_poll') {
      $post = Post::create($request->merge(['total_votes' => 0])->only(['content', 'visibility', 'type', 'total_votes']));
      $pollsData = [];
      foreach ($request->polls as $pollName) {
        $pollData = [
          'post_id' => $post->id,
          'name' => $pollName
        ];
        array_push($pollsData, $pollData);
      }
      Poll::insert($pollsData);
    } else if ($request->type === 'image_poll') {
      $post = Post::create($request->merge(['total_votes' => 0])->only(['content', 'visibility', 'type', 'total_votes']));
      $pollsData = [];
      for ($i = 0; $i < count($request->polls); $i++) {
        $poll = new Poll;
        $poll->post_id = $post->id;
        $poll->name = $request->polls[$i];
        $poll->image_url = $poll->attachFile('poll_image-'.$i, $request->poll_images[$i]);
        $poll->save();
      }
    } else if ($request->type === 'text') {
      $post = Post::create($request->only(['content', 'visibility', 'type']));
      if (isset($request->images)) {
        $imageUrls = $post->attachFiles($request->images, true);
      }
    } else if ($request->type === 'shared') {
      $post = Post::create($request->only(['content', 'visibility', 'type', 'shared_id']));
    }

    if ($post) {
      if ($post->visibility === 'scheduled') {
        PublishPost::dispatch($post, true)->delay($request->publish_at);
      }
      return ['success' => true,
        'message' => 'Post successfully created!'];
    }
    return response()->json(['success' => $result,
      'message' => 'Failed to create post!'], 451);
  }

  // Update a Community Post
  public function updatePost(Request $request, $id) {
    $validated = $request->validate([
      'content' => 'bail|required|string'
    ]);
    $post = Post::find($id);
    if (!$request->user()->can('update', [Post::class, $post])) {
      abort(405);
    }
    $result = $post->update($validated);
    if ($result) {
      return ['success' => true,
        'message' => 'Post successfully updated!'];
    }
    return response()->json(['success' => $result,
      'message' => 'Failed to update post!'], 451);
  }

  // Delete a Community Post
  public function deletePost($id) {
    $post = Post::find($id);
    if (!auth()->user()->can('delete', [Post::class, $post])) {
      abort(405);
    }
    $result = $post->delete();
    if ($result) {
      return ['success' => true,
        'message' => 'Post successfully updated!'];
    }
    return response()->json(['success' => $result,
      'message' => 'Failed to update post!'], 451);
  }

  // Like and dislike on a Community Post
  public function reviewPost(Request $request, $id) {
    $request->validate([
      'review' => 'bail|required|in:0,1'
    ]);
    $post = Post::find($id);
    if (!$request->user()->can('review', [Post::class, $post])) {
      abort(405);
    }
    $reviewed = $post->reviewed();
    if ($reviewed === $request->review) {
      $post->unreview();
      return response()->noContent();
    }
    $result = $post->review($request->review);
    if ($result) {
      return response()->noContent();
    }
    return response()->json(['success' => false], 451);
  }

  // Get what is the review of user on a post
  public function getPostReview(Request $request, $id) {
    $review_code = Post::find($id)->reviewed();
    return ['review' => $review_code];
  }

  // Create comment on a Community Post
  public function createPostComment(Request $request, $id) {
    $request->validate([
      'text' => 'bail|required|string|max:300'
    ]);
    $commenter_id = $request->user()->id;
    $post = Post::find($id);
    if (!$request->user()->can('comment', [Post::class, $post])) {
      abort(405);
    }
    $comment = $post->comment($request->text);
    if ($comment) {
      if ($post->channel_id !== $commenter_id) {
        //notification
      }
      return response()->noContent();
    }
    return abort(451);
  }

  // Get all comments of a specific post
  public function getPostComments(Request $request, $id) {
    $post = Post::find($id);
    if (!$request->user()->can('readComments', [Post::class, $post])) {
      abort(405);
    }
    $comment_query = $post->comments();
    if (isset($request->limit)) {
      $offset = isset($request->offset)
      ?$request->offset
      :0;
      $comment_query->offset($offset)->limit($request->limit);
    }
    $comments = $comment_query->get();
    foreach ($comments as $comment) {
      $comment->review = $comment->reviewed();
      $comment->author = ($comment->commenter_id === $post->channel_id);
    }
    return $comments;
  }

  // Vote a poll
  public function votePoll($id) {
    $poll = Poll::find($id);
    $post = $poll->post;
    if (!auth()->user()->can('vote', [Post::class, $post])) {
      abort(405);
    }
    $voted_poll = $this->getVotedPoll($post->id);
    if (is_null($voted_poll)) {
      $vote = Vote::create([
        'poll_id' => $id,
        'post_id' => $post->id,
      ]);
      $poll->increment('vote_count', 1);
      $post->increment('total_votes', 1);
    }
    else {
     Vote::where('voter_id', auth()->id())->where('post_id', $post->id)->delete();
      if ($voted_poll->id !== $id) {
        $vote = Vote::create([
          'poll_id' => $id,
          'post_id' => $post->id,
        ]);
        $voted_poll->decrement('vote_count', 1);
        $poll->increment('vote_count', 1);
      }
      else{
        $voted_poll->decrement('vote_count', 1);
        $post->decrement('total_votes', 1);
      }
    }
    return is_null($vote)
      ?response()->json(['success' => false], 451)
      :response()->noContent();
  }

  // Get which poll user is voted of a post
  protected function getVotedPoll($id) {
    return Vote::where('voter_id', auth()->id())->where('post_id', $id)->first();
  }
  // Sent notification to user
  protected function notify($emails, $data, $type) {
    if ($type === 'video') {
      foreach ($emails as $email) {
        Mail::to($email)->send(new VideoUploadedMail($data));
      }
    } else if ($type === 'comment') {
      Mail::to($emails)->send(new CommentedMail($data));
    } else if ($type === 'reply') {
      Mail::to($emails)->send(new RepliedMail($data));
    } else if ($type === 'liked') {
      Mail::to($emails)->send(new LikedMail($data));
    } else if ($type === 'heart') {
      Mail::to($emails)->send(new GotHeartMail($data));
    } else {
      return false;
    }
  }
}