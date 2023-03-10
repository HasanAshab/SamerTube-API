<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use App\Rules\CSVRule;
use App\Models\User;
use App\Models\Video;
use App\Models\View;
use App\Models\Channel;
use App\Models\Subscriber;
use App\Models\Notification;
use App\Models\Hidden;
use App\Models\WatchLater;
use App\Models\Report;
use App\Events\Watched;
use App\Jobs\PublishVideo;
use App\Mail\VideoUploadedMail;
use App\Mail\CommentedMail;
use App\Mail\RepliedMail;
use App\Mail\LikedMail;
use App\Mail\GotHeartMail;
use Carbon\Carbon;
use Mail;
use DB;

class VideoController extends Controller
{
  // Get all public videos
  public function explore(Request $request) {
    $video_query = Video::with(['channel' => function ($query){
      return $query->select('id', 'name', 'logo_url');
    }]);
    if (auth()->check() && auth()->user()->is_admin) {
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
        // notify
      }
      else if ($video->visibility === 'scheduled') {
        PublishVideo::dispatch($video, true)->delay($request->publish_at);
      }
      return ['success' => $result,
        'message' => 'Video successfully uploaded!'];
    }
    return response()->json(['success' => $result,
      'message' => 'Failed to upload video!'], 422);

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
    ], 422);

  }

  // Get details of a video to watch
  public function watch(Request $request, $id) {
    $video = Video::where('id', $id)->channel()->select('videos.*', 'channels.name', 'channels.logo_url', 'channels.total_subscribers')->first();
    if (!$request->user()->can('watch', [Video::class, $video]) || (!auth()->check() && $video->visibility !== 'public')) {
      abort(405);
    }
    if(auth()->check() && $request->user()->watch_history){
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
    :response()->json(['success' => false], 422);
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
    ], 422);
  }
  
  // Get liked videos
  public function getLikedVideos(Request $request) {
    return Video::liked($request->limit, $request->offset);
  }
  
  // Get videos of a channel [no param for own videos]
  public function getChannelVideos(Request $request, $id = null) {
    if(!auth()->check() && is_null($id)){
      abort(405);
    }
    $id = $id??$request->user()->id;
    $video_query = auth()->check() && ($request->user()->is_admin || $request->user()->id === $id)
      ?Video::where('channel_id', $id)->latest()
      :Video::where('channel_id', $id)->where('visibility', 'public')->latest();
    if(isset($request->limit)){
      $offset = isset($request->offset)
        ?$request->offset
        :0;
      $video_query->offset($offset)->limit($request->limit);
    }
    $videos = $video_query->get();
    return $videos;
  } 
}