<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Video;
use App\Models\Post;
use App\Models\Subscriber;
use DB;

class ExploreController extends Controller
{
  // Get all contents for explore tab
  public function __invoke(Request $request) {
    $video_query = Video::where('visibility', 'public')->with(['channel' => function ($query) {
      return $query->select('id', 'name', 'logo_url');
    }]);
    $isLoggedIn = auth()->check();
    $videos = $video_query->rank()->select(['*', DB::raw("'video' as type")])->get();
    if ($isLoggedIn) {
      $id = auth()->id();
      $subscriptions_id = Subscriber::where('subscriber_id', $id)->pluck('channel_id');
      $posts = Post::with('polls', 'polls.voted', 'reviewed')->whereIn('channel_id', $subscriptions_id)->where('visibility', 'public')->get();
      $posts->each(function ($post) {
      if($post->total_votes > 0){
        $post->polls->each(function ($poll) use ($post){
          $poll->vote_rate = ($poll->vote_count*100)/$post->total_votes;
        });
      }
    });
      $combined = [];
      $video_count = 0;
      $post_count = 0;
      $i = 0;
      while ($video_count < count($videos) || $post_count < count($posts)) {
        if ($i % 4 == 0 && $post_count < count($posts) && count($combined) > 0) {
          $combined[] = $posts[$post_count];
          $post_count++;
        } else if ($video_count < count($videos)) {
          $combined[] = $videos[$video_count];
          $video_count++;
        }
        $i++;
      }
      if (isset($request->limit)) {
        $offset = $request->get('offset', 0);
        $combined = collect($combined)->slice($offset, $request->limit)->values();
      }
      return $combined;
    }
    return $videos;
  }
}