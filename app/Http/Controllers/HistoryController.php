<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\History;

class HistoryController extends Controller
{
  //Get watch history
  public function index() {
    $id = auth()->id();
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

  // Delete history
  public function destroy($id) {
    $history = History::find($history_id);
    if ($history->user_id === auth()->id()) {
      $result = $history->delete();
      if ($result) {
        return ['success' => true,
          'message' => 'History deleted!'];
      }
      return response()->json([
        'success' => false,
        'message' => 'Failed to delete history!'
      ], 422);
    }
    abort(405);
  }
  
  
}