<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use App\Models\Video;
use App\Models\Playlist;
use App\Models\PlaylistVideo;
use App\Models\SavedPlaylist;

class PlaylistController extends Controller
{
  // Get a users saved, created and liked videos Playlist
  public function index() {
    $saved_playlists_id = SavedPlaylist::where('user_id', auth()->id())->pluck('playlist_id');
    $liked_videos_playlist = [
      'name' => 'Liked videos',
      'total_videos' => Review::where('reviewer_id', auth()->id())->where('review', 1)->count(),
      'link' => route('videos.liked'),
      'thumbnail_url' => route('static.image.serve', ['filename' => 'liked-videos.jpg'])
    ];
    $watch_later = [
      'name' => 'Watch later',
      'total_videos' => WatchLater::where('user_id', auth()->id())->count(),
      'link' => route('videos.watchLater'),
      'thumbnail_url' => route('static.image.serve', ['filename' => 'watch-later.jpg'])
    ];
    $playlists = Playlist::where('user_id', auth()->id())->orWhere(function ($query) use ($saved_playlists_id) {
      $query->whereIn('id', $saved_playlists_id);
    })->orderByDesc('updated_at')->get();

    $playlists->prepend($liked_videos_playlist);
    $playlists->prepend($watch_later);
    return $playlists;
  }

  // Create a playlist
  public function store(Request $request) {
    $request->validate([
      'name' => 'required|string|between:1,30',
      'description' => 'string|max:300',
      'visibility' => 'required|in:public,private',
    ]);
    $playlist = Playlist::create([
      'name' => $request->name,
      'description' => $request->description,
      'visibility' => $request->visibility
    ]);
    if ($playlist) {
      return ['success' => true,
        'message' => 'Playlist successfully created!'];
    }
    return response()->json([
      'success' => false,
      'message' => 'Failed to create playlist!'
    ], 422);
  }

  // Update a playlist
  public function update(Request $request, $id) {
    $request->validate([
      'name' => 'required|string|between:1,30',
      'description' => 'string|max:300',
      'visibility' => 'required|in:public,private',
    ]);
    $playlist = Playlist::find($id);
    if ($playlist->user_id !== $request->user()->id) {
      abort(405);
    }
    $result = $playlist->update($request->only(['name', 'description', 'visibility']));
   if ($result) {
      return ['success' => true,
        'message' => 'Playlist successfully updated!'];
    }
    return response()->json([
      'success' => false,
      'message' => 'Failed to update playlist!'
    ], 422);
  }

  // Remove a playlist
  public function destroy($id) {
    $playlist = Playlist::find($id);
    if (!auth()->user()->is_admin && $playlist->user_id !== auth()->id()) {
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
    ], 422);
  }

  // Save others public playlist
  public function savePlaylist($id) {
    if (SavedPlaylist::where('user_id', auth()->id())->where('playlist_id', $id)->exists()) {
      return response()->json([
        'success' => false,
        'message' => 'Playlist already exist in library!'
      ], 422);
    }
    $playlist = Playlist::find($id);
    if ($playlist->visibility === "private" && !auth()->user()->is_admin) {
      abort(405);
    }
    if ($playlist->user_id === auth()->id()) {
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
    ], 422);
  }

  // Remove saved playlist
  public function removeSavedPlaylist($id) {
    $result = SavedPlaylist::where('user_id', auth()->id())->where('playlist_id', $id)->delete();
    if ($result) {
      return [
        'success' => true,
        'message' => 'Playlist removed from library!'
      ];
    }
    return response()->json([
      'success' => false,
      'message' => 'Failed removing playlist from library!'
    ], 422);
  }

  // Add video to playlist
  public function addVideoToPlaylist($playlist_id, $video_id) {
    $playlist = Playlist::find($playlist_id);
    if ($playlist->user_id !== auth()->id()) {
      abort(405);
    }
    $playlist_video_exists = PlaylistVideo::where('playlist_id', $playlist_id)->where('video_id', $video_id)->exists();
    if ($playlist_video_exists) {
      return response()->json([
        'success' => false,
        'message' => 'Video already exist in the playlist!'
      ], 422);
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
    ], 422);
  }

  // Remove video from a playlist
  public function removeVideoFromPlaylist($playlist_id, $video_id) {
    $playlist = Playlist::find($playlist_id);
    if ($playlist->user_id !== auth()->id()) {
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
    ], 422);
  }

  // Get all videos of a playlist
  public function getPlaylistVideos($id) {
    $playlist = Playlist::find($id);
    if ($playlist->visibility !== "public" && !auth()->user()->is_admin && $playlist->user_id !== auth()->id()) {
      abort(405);
    }
    $videos = collect();
    $playlist_video_query = $playlist->videos();
    if (isset($request->limit)) {
      $offset = isset($request->offset)?$request->offset:0;
      $playlist_video_query->offset($offset)->limit($request->limit);
    }
    $videos = $playlist_video_query->with(['channel' => function ($query) {
      return $query->select('id', 'name');
    }])->where('visibility', 'public')->get();
    return $videos;
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
    $videos = Video::with(['channel' => function ($query) {
      return $query->select('id', 'name');
    }])->whereIn('id', $watch_later_videos_id)->get();
    return $videos;
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
      ], 422);
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
    ], 422);
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
    ], 422);
  }

}