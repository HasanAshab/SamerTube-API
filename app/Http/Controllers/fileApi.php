<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Iman\Streamer\VideoStreamer;
use App\Models\Video;
use App\Models\Channel;


class fileApi extends Controller
{
    public function index($type, $id=null){
      if($type === "video"){
        $video = Video::find($id);
        if($video === null || $video->visibility === 'private'){
          return null;
        }
        $video_path = $video->video_path;
        $path = storage_path("app/public/$video_path");
        VideoStreamer::streamFile($path);
      }
      else if($type === "thumbnail"){
        $video = Video::find($id);
        if($video === null || $video->visibility === 'private'){
          return null;
        }
        $thumbnail_path = $video->thumbnail_path;
        $path = storage_path("app/public/$thumbnail_path");
        return response()->file($path);
      }
      else if($type === "logo"){
        $channel = Channel::find($id);
        if($channel === null){
          return null;
        }
        $logo_path = $channel->logo_path;
        $path = storage_path("app/public/$logo_path");
        return response()->file($path);
      }
      else if($type === "liked-videos"){
        return response()->file(storage_path("app/public/assets/liked_videos.png"));
      }
      else if($type === "company-logo"){
        return response()->file(storage_path("app/public/assets/company-logo.jpg"));
      }
      else if($type === "watch-later"){
        return response()->file(storage_path("app/public/assets/watch_later.png"));
      }
      return null;
    }
}
