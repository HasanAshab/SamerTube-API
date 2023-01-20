<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\View;
use App\Models\Video;
use App\Models\Subscriber;
use Carbon\Carbon;
use DB;

class DashboardApi extends Controller
{
  // Get Analytics for Overview tab
  public function getOverviewData() {
    $date = Carbon::now()->subDays(28);
    $id = auth()->user()->id;
    $videos_id = Video::where('channel_id', $id)->where('visibility', 'public')->pluck('id');
    $views = View::whereIn('video_id', $videos_id)->whereDate('created_at', '>=', $date);
    $views_total = $views->count();
    $watch_time_total = $views->sum('view_duration');
    $views_and_watch_time_analytics = $views->select(DB::raw('DATE_FORMAT(DATE(created_at), "%d-%b-%Y") as date'), DB::raw('count(*) AS view'), DB::raw('sum(view_duration) AS watch_time'))->groupBy('date')->get();

    $subscribers = Subscriber::where('channel_id', $id)->whereDate('created_at', '>=', $date);
    $subscribers_total = $subscribers->count();
    $subscribers_analytics = $subscribers->select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) AS subscribe'))->groupBy('date')->get();
    
    $top_contents = View::with(['video' => function($query){
      $query->select('id', 'thumbnail_url', 'title', 'average_view_duration', 'view_count');
    }])->whereIn('video_id', $videos_id)->whereDate('created_at', '>=', $date)->select('video_id', DB::raw('count(*) AS views'))->groupBy('video_id')->orderByDesc('views')->limit(10)->get();
  
  foreach ($top_contents as $top_content){
    $top_content->video->makeVisible('average_view_duration');
  }
    return [
      'views_and_watch_time_data' => [
        'total_views' => $views_total,
        'total_watch_time' => $watch_time_total,
        'analytics' => $views_and_watch_time_analytics
      ],
      'subscribers_data' => [
        'total' => $subscribers_total,
        'analytics' => $subscribers_analytics
      ],
      'top_contents' => $top_contents
    ];
  }
  
  // Get percentage of Audience country
  public function getChannelAudienceCountry() {
    $videos_id = Video::where('channel_id', auth()->user()->id)->where('visibility', 'public')->pluck('id');
    $total_views = auth()->user()->channel->views->count();
    $views = View::whereIn('video_id', $videos_id)->join('channels AS viewer', 'viewer.id', '=', 'views.user_id')->select('viewer.country', DB::raw('CAST(((COUNT(viewer.country) * 100) / '.$total_views.') as INTEGER) AS percentage'))->groupBy('viewer.country')->get();
    return $views;
  }

  // Get percentage of Audience country of a Video
  public function getVideoAudienceCountry($id) {
    $video = Video::find($id);
    if (!$video->channel_id === auth()->user()->id) {
      return accessDenied();
    }
    $views = View::where('video_id', $id)->join('channels AS viewer', 'viewer.id', '=', 'views.user_id')->select('viewer.country', DB::raw('CAST(((COUNT(viewer.country) * 100) / '.$video->view_count.') as INTEGER) AS percentage'))->groupBy('viewer.country')->get();
    return $views;
  }

}