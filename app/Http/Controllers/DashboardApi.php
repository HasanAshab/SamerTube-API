<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Channel;
use App\Models\View;
use App\Models\Video;
use App\Models\Subscriber;
use App\Models\Comment;
use App\Models\Review;
use Carbon\Carbon;
use DB;

class DashboardApi extends Controller
{
  // Get Analytics of Channel for Overview tab
  public function getChannelOverview() {
    $date = Carbon::now()->subDays(28);
    $id = auth()->user()->id;
    $videos_id = Video::where('channel_id', $id)->where('visibility', 'public')->pluck('id');
    $views = View::whereIn('video_id', $videos_id)->whereDate('created_at', '>=', $date);
    $views_total = $views->count();
    $watch_time_total = $views->sum('view_duration');
    $views_and_watch_time_analytics = $views->select(DB::raw('DATE_FORMAT(DATE(created_at), "%d %b %Y") as date'), DB::raw('count(*) AS view'), DB::raw('sum(view_duration) AS watch_time'))->groupBy('date')->get();

    $subscribers = Subscriber::where('channel_id', $id)->whereDate('created_at', '>=', $date);
    $subscribers_total = $subscribers->count();
    $subscribers_analytics = $subscribers->select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) AS subscribe'))->groupBy('date')->get();

    $top_contents = View::with(['video' => function($query) {
      $query->select('id', 'thumbnail_url', 'title', 'average_view_duration', 'view_count', 'duration');
    }])->whereIn('video_id', $videos_id)->whereDate('created_at', '>=', $date)->select('video_id', DB::raw('count(*) AS views'))->groupBy('video_id')->orderByDesc('views')->limit(10)->get();
    foreach ($top_contents as $top_content) {
      $top_content->video->makeVisible('average_view_duration');
      $average_view_sec = $top_content->video->getAttributes()['duration']*($top_content->video->average_view_duration/100);
      $top_content->video->average_view_duration = ($average_view_sec < 3600)?gmdate("i:s", $average_view_sec):gmdate("H:i:s", $average_view_sec);
    }
    return [
      'views_and_watch_time' => [
        'total_views' => $views_total,
        'total_watch_time' => $watch_time_total,
        'analytics' => $views_and_watch_time_analytics
      ],
      'subscribers' => [
        'total' => $subscribers_total,
        'analytics' => $subscribers_analytics
      ],
      'top_contents' => $top_contents
    ];
  }

  // Get Analytics of Channel for Audience tab
  public function getChannelAudience() {
    $id = auth()->user()->id;
    $videos_id = Video::where('channel_id', $id)->where('visibility', 'public')->pluck('id');
    $subscribers_id = Subscriber::where('channel_id', $id)->pluck('id');
    $total_views = auth()->user()->channel->views->count();
    $top_geographies = View::whereIn('video_id', $videos_id)->join('channels AS viewer', 'viewer.id', '=', 'views.user_id')->select('viewer.country', DB::raw('CAST(((COUNT(viewer.country) * 100) / '.$total_views.') as INTEGER) AS percentage'))->groupBy('viewer.country')->orderByDesc('percentage')->limit(5)->get();
    $total_watch_time = Channel::whereId($id)->value('total_watch_time');

    $subscribed = View::whereIn('video_id', $videos_id)->whereIn('user_id', $subscribers_id)->sum('view_duration')/3600;
    $unsubscribed = View::whereIn('video_id', $videos_id)->whereNotIn('user_id', $subscribers_id)->sum('view_duration')/3600;
    if ($total_watch_time == 0) {
      $watch_time_from_subscribed = null;
      $watch_time_from_unsubscribed = null;
    } else {
      $watch_time_from_subscribed = ($subscribed*100)/$total_watch_time;
      $watch_time_from_unsubscribed = ($unsubscribed*100)/$total_watch_time;
    }
    return [
      'top_geographies' => $top_geographies,
      'watch_time_from' => [
        'subscribed' => $watch_time_from_subscribed,
        'unsubscribed' => $watch_time_from_unsubscribed
      ]
    ];
  }

  // Get Analytics of a Video for Overview tab
  public function getVideoOverview($video_id) {
    $id = auth()->user()->id;
    $video = Video::find($video_id);
    if ($id !== $video->channel_id) {
      return accessDenied();
    }
    $views = View::where('video_id', $video_id);
    $views_total = $views->count();
    $watch_time_total = $views->sum('view_duration');
    $views_and_watch_time_analytics = $views->select(DB::raw('DATE_FORMAT(DATE(created_at), "%d-%b-%Y") as date'), DB::raw('count(*) AS view'), DB::raw('sum(view_duration) AS watch_time'))->groupBy('date')->get();

    $subscribers_analytics = Subscriber::withTrashed()->where('channel_id', $id)->where('video_id', $video_id)->select(DB::raw('DATE(created_at) as date'), DB::raw('SUM(status) AS subscribe'))->groupBy('date')->get();
    $subscribers_total = $subscribers_analytics->sum('subscribe');

    return [
      'views_and_watch_time' => [
        'total_views' => $views_total,
        'total_watch_time' => $watch_time_total,
        'analytics' => $views_and_watch_time_analytics
      ],
      'subscribers' => [
        'total' => $subscribers_total,
        'analytics' => $subscribers_analytics
      ]
    ];
  }

  // Get Analytics of a Video for Engagement tab
  public function getVideoEngagement($video_id) {
    $id = auth()->user()->id;
    $video = Video::find($video_id)->makeVisible('average_view_duration');
    if ($id !== $video->channel_id) {
      return accessDenied();
    }

    $average_view_sec = $video->getAttributes()['duration']*($video->average_view_duration/100);
    $average_view_duration = ($average_view_sec < 3600)?gmdate("i:s", $average_view_sec):gmdate("H:i:s", $average_view_sec);
    $average_view_duration_analytics = View::where('video_id', $video_id)->select(DB::raw('DATE_FORMAT(DATE(created_at), "%d %b %Y") as date'), DB::raw('TIME_FORMAT(SEC_TO_TIME(avg(view_duration)), "%i:%s") AS average_view_duration'))->groupBy('date')->get();

    $reviews = Review::where('video_id', $video_id);
    $total_reviews = $reviews->count();
    $likes = $reviews->where('review', 1)->count();
    $dislikes = $reviews->where('review', 0)->count();

    $comments = Comment::where('video_id', $video_id);
    $total_comments = $comments->count();
    $comments_analytics = $comments->select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) AS comment'))->groupBy('date')->get();

    return [
      'view_duration' => [
        'average_view_duration' => $average_view_duration,
        'analytics' => $average_view_duration_analytics
      ],
      'review' => [
        'total' => $total_reviews,
        'likes' => $likes,
        'dislikes' => $dislikes
      ],
      'comment' => [
        'total' => $total_comments,
        'analytics' => $comments_analytics
      ]
    ];
  }

  // Get Video Perfomance
  public function getVideoPerfomance($video_id) {
    $id = auth()->user()->id;
    $video = Video::find($video_id)->makeVisible('average_view_duration');
    if ($id !== $video->channel_id) {
      return accessDenied();
    }
    $average_view_sec = $video->getAttributes()['duration']*($video->average_view_duration/100);
    $video->average_view_duration = ($average_view_sec < 3600)?gmdate("i:s", $average_view_sec):gmdate("H:i:s", $average_view_sec);
    $video_rank = $this->getPreviousRankedVideos()->search(function ($video) use ($video_id) {
      return $video->id === (int)$video_id;
    }) + 1;
    $video->rank = $video_rank;
    return $video;
  }

  // Get Analytics of Video for Audience tab
  public function getVideoAudience($video_id) {
    $id = auth()->user()->id;
    $video = Video::find($video_id)->makeVisible(['average_view_duration', 'watch_time']);
    if ($id !== $video->channel_id) {
      return accessDenied();
    }
    $subscribers_id = Subscriber::where('channel_id', $id)->pluck('id');
    $top_geographies = View::where('video_id', $video_id)->join('channels AS viewer', 'viewer.id', '=', 'views.user_id')->select('viewer.country', DB::raw('CAST(((COUNT(viewer.country) * 100) / '.$video->view_count.') as INTEGER) AS percentage'))->groupBy('viewer.country')->orderByDesc('percentage')->limit(5)->get();

    $subscribed = View::where('video_id', $video_id)->whereIn('user_id', $subscribers_id)->sum('view_duration')/3600;
    $unsubscribed = View::where('video_id', $video_id)->whereNotIn('user_id', $subscribers_id)->sum('view_duration')/3600;
    if ($video->watch_time == 0) {
      $watch_time_from_subscribed = null;
      $watch_time_from_unsubscribed = null;
    } else {
      $watch_time_from_subscribed = ($subscribed*100)/$video->watch_time;
      $watch_time_from_unsubscribed = ($unsubscribed*100)/$video->watch_time;
    }
    return [
      'top_geographies' => $top_geographies,
      'watch_time_from' => [
        'subscribed' => $watch_time_from_subscribed,
        'unsubscribed' => $watch_time_from_unsubscribed
      ]
    ];
  }
  
  // Get previous 10 videos ranked by views
  public function getPreviousRankedVideos() {
    $id = auth()->user()->id;
    //$videos = Video::where('channel_id', $id)->latest()->limit(10)->get()->sortByDesc('view_count')->values();
    $videos = Video::where('channel_id', $id)->sortByDesc('view_count')->latest()->limit(10)->get();
    return $videos;
  }
}