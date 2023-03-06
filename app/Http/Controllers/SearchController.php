<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use App\Models\Video;
use App\Models\Playlist;
use App\Models\Channel;
use App\Models\Tag;
use App\Models\History;
use App\Events\Searched;
use DB;

class SearchController extends Controller
{
  
  // perform search of Video, Playlist and Channel
  public function search(Request $request, $term) {
    $request->validate([
      'category_id' => 'exists:categories,id',
      'sort' => 'bail|required|string|in:relevance,view,date,rating',
      'type' => 'bail|required|string|in:all,video,channel,playlist',
      'date_range' => 'bail|required|string|in:anytime,hour,day,week,month,year',
    ]);
    
    if(auth()->check()){
      event(new Searched(auth()->user(), $term));
    }
    $offset = isset($request->offset)
      ?$request->offset
      :0;
    
    if ($request->type === 'all' || $request->type === 'video') {
      $video_query = Video::search($term, true, true)->date($request->date_range);
      if (!(auth()->check() && $request->user()->is_admin)) {
        $video_query->where('visibility', 'public');
      }
      if ($request->category_id !== null) {
        $video_query->where('category_id', $request->category_id);
      }
      $videos = $video_query->channel(['name', 'logo_url'])->get();
      $videos->each(function ($video) use ($term) {
        $video->type = 'video';
        if (!str_contains(mb_strtolower($video->title), mb_strtolower($term))) {
          $video->unmatched = 800;
        } else {
          $video->unmatched = levenshtein($term, $video->title);
        }
      });
      if ($request->type === 'video') {
        $results = collect($videos);
      }
    }
    if ($request->type === 'all' || $request->type === 'playlist') {
      $playlist_query = Playlist::search($term, false, true)->date($request->date_range);
      if (!(auth()->check() && $request->user()->is_admin)) {
        $playlist_query->where('visibility', 'public');
      }
      $playlists = $playlist_query->get();
      $playlists->each(function ($playlist) use ($term) {
        $playlist->type = 'playlist';
        if (!str_contains(mb_strtolower($playlist->name), mb_strtolower($term))) {
          $playlist->unmatched = 900;
        } else {
          $playlist->unmatched = levenshtein($term, $playlist->name);
        }
      });
      if ($request->type === 'playlist') {
        $results = collect($playlists);
      }
    }
    if ($request->type === 'all' || $request->type === 'channel') {
      $channels = Channel::search($term, true, true)->date($request->date_range)->get();
      $channels->each(function ($channel) use ($term) {
        $channel->type = 'channel';
        if (!str_contains(mb_strtolower($channel->name), mb_strtolower($term))) {
          $channel->unmatched = 1000;
        } else {
          $channel->unmatched = levenshtein($term, $channel->name);
        }
      });
      if ($request->type === 'channel') {
        $results = collect($channels);
      }
    }

    if ($request->type === 'all') {
      $results = collect()->merge($videos)->merge($playlists)->merge($channels);
    }
    $results = $this->rank($results, $request->sort, $request->type);
    
    if(isset($request->limit)){
      return $results->slice($offset, $request->limit)->values();
    }
    return $results;
  }

  // Get search suggestion
  public function suggestions(Request $request, $query = null) {
    $authUser = auth()->check();
    $history = ($authUser)
      ?History::where('user_id', $request->user()->id)->where('search_term', 'like', '%'.$query.'%')->select('search_term as suggestion', DB::raw('true as history'))->latest()->limit(20)->get()
      :null;
    
    if ($authUser && $query === null) {
      return $history;
    }
    
    $suggestions = cache()->get('search:'.$query);
    
    if (is_null($suggestions)){
      $titles = Video::where('title', 'like', '%'.$query.'%')->select('title as suggestion', DB::raw('false as history'))->limit(5)->get();
      $tags = Tag::where('name', 'like', '%'.$query.'%')->select('name as suggestion', DB::raw('false as history'))->limit(5)->get();
      $channels = Channel::where('name', 'like', '%'.$query.'%')->select('name as suggestion', DB::raw('false as history'))->limit(5)->get();
      $playlists = Playlist::where('name', 'like', '%'.$query.'%')->select('name as suggestion', DB::raw('false as history'))->limit(5)->get();
      $suggestions = collect()->merge($titles)->merge($tags)->merge($channels)->merge($playlists);
      cache()->put('search:'.$query, $suggestions, 24 * 60);
    }
    
    $mixed_suggestions = collect()->merge($history)->merge($suggestions)->take(20);

    $mixed_suggestions = $mixed_suggestions->sortBy(function ($suggestion) use ($query){
      return levenshtein($query, $suggestion->suggestion);
    });
    $results = $mixed_suggestions->values();

    return $results;
  }

  protected function rank($collection, $sort_by, $type) {
    $grouped = $collection->groupBy('type');
    if (isset($grouped['video'])) {
      $grouped['video'] = $grouped['video']->sortBy(Video::search_rankable($sort_by))->values();
    }
    else if (isset($grouped['channel'])) {
      $grouped['channel'] = (array_key_exists($sort_by, Channel::search_rankable($sort_by)))
        ?$grouped['channel']->sortBy(Channel::search_rankable($sort_by))->values()
        :$grouped['channel']->sortBy(Channel::search_rankable($sort_by))->values();
    }
    else if (isset($grouped['playlist'])) {
      $grouped['playlist'] = (array_key_exists($sort_by, Playlist::search_rankable($sort_by)))
        ?$grouped['playlist']->sortBy(Playlist::search_rankable($sort_by))->values()
        :$grouped['playlist']->sortBy(Playlist::search_rankable($sort_by))->values();
    }
    return $grouped->flatten(1);
  }
}